<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaService
{
    public function requestUpload(User $uploader, string $fileName): array
    {
        $path = config('media.tmp_prefix') . Str::uuid7();

        /** @var FilesystemAdapter $s3 */
        $s3 = Storage::disk('s3');

        ['url' => $uploadUrl] = $s3->temporaryUploadUrl($path, now()->addHour());

        $media = Media::create([
            'created_by' => $uploader->id,
            'disk' => 's3',
            'path' => $path,
            'file_name' => $fileName,
        ]);

        return [
            'media_id' => $media->id,
            'upload_url' => $uploadUrl,
        ];
    }

    /**
     * Attach a pending media file to an entity.
     *
     * Flow:
     * - Deletes any existing media already attached to the entity.
     * - If the media path starts with the tmp prefix, moves the file to the media
     *   prefix, fetches real metadata from storage (mime_type, size), validates them,
     *   then marks it as uploaded.
     * - If the media is already finalized, simply re-attaches it.
     *
     * @param  string[]  $allowedMimeTypes
     *
     * @throws ValidationException when the file is missing, has wrong type, or is too large
     */
    public function attachToEntity(
        Media $media,
        Model $entity,
        array $allowedMimeTypes = [],
        int $maxSizeBytes = 0,
    ): Media {
        if (empty($allowedMimeTypes)) {
            $allowedMimeTypes = (array) config('media.allowed_mime_types');
        }

        if ($maxSizeBytes === 0) {
            $maxSizeBytes = (int) config('media.max_size');
        }

        return DB::transaction(function () use ($media, $entity, $allowedMimeTypes, $maxSizeBytes): Media {
            $this->deleteExistingMediaForEntity($entity);

            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk($media->disk);

            if (str_starts_with($media->path, config('media.tmp_prefix'))) {
                if (!$disk->exists($media->path)) {
                    throw ValidationException::withMessages([
                        'media_id' => __('validation.custom.media.not_found'),
                    ]);
                }

                $mimeType = $disk->mimeType($media->path) ?: $media->mime_type;
                $size = $disk->size($media->path);

                if (!in_array($mimeType, $allowedMimeTypes, true)) {
                    $media->delete();

                    throw ValidationException::withMessages([
                        'media_id' => __('validation.custom.media.invalid_type'),
                    ]);
                }

                if ($size > $maxSizeBytes) {
                    $media->delete();

                    throw ValidationException::withMessages([
                        'media_id' => __('validation.custom.media.too_large'),
                    ]);
                }

                $newPath = config('media.media_prefix') . basename($media->path);
                $disk->move($media->path, $newPath);

                $media->forceFill([
                    'path' => $newPath,
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'mediable_id' => $entity->getKey(),
                    'mediable_type' => $entity->getMorphClass(),
                    'uploaded_at' => now(),
                ])->save();
            } else {
                $media->forceFill([
                    'mediable_id' => $entity->getKey(),
                    'mediable_type' => $entity->getMorphClass(),
                    'uploaded_at' => $media->uploaded_at ?? now(),
                ])->save();
            }

            return $media;
        });
    }

    /**
     * Validate that a pending media file was successfully uploaded to storage,
     * check its MIME type and size, then mark it as confirmed (sets uploaded_at).
     *
     * @param  string[]  $allowedMimeTypes
     *
     * @throws ValidationException when the file is missing, has wrong type, or is too large
     */
    public function confirmUpload(
        Media $media,
        array $allowedMimeTypes = [],
        int $maxSizeBytes = 0,
    ): Media {
        if (empty($allowedMimeTypes)) {
            $allowedMimeTypes = (array) config('media.allowed_mime_types');
        }

        if ($maxSizeBytes === 0) {
            $maxSizeBytes = (int) config('media.max_size');
        }

        if ($media->uploaded_at !== null) {
            return $media;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($media->disk);

        if (!$disk->exists($media->path)) {
            throw ValidationException::withMessages([
                'media_id' => __('validation.custom.media.not_found'),
            ]);
        }

        $mimeType = $disk->mimeType($media->path) ?: $media->mime_type;
        $size = $disk->size($media->path);

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            $media->delete();

            throw ValidationException::withMessages([
                'media_id' => __('validation.custom.media.invalid_type'),
            ]);
        }

        if ($size > $maxSizeBytes) {
            $media->delete();

            throw ValidationException::withMessages([
                'media_id' => __('validation.custom.media.too_large'),
            ]);
        }

        $newPath = str_starts_with($media->path, config('media.tmp_prefix'))
            ? config('media.media_prefix') . basename($media->path)
            : $media->path;

        if ($newPath !== $media->path) {
            $disk->move($media->path, $newPath);
        }

        $media->forceFill([
            'path' => $newPath,
            'mime_type' => $mimeType,
            'size' => $size,
            'uploaded_at' => now(),
        ])->save();

        return $media;
    }

    public function deleteExistingMediaForEntity(Model $entity): void
    {
        Media::query()
            ->where('mediable_type', $entity->getMorphClass())
            ->where('mediable_id', $entity->getKey())
            ->get()
            ->each(fn(Media $m) => $m->delete());
    }

    public function delete(Media $media): void
    {
        $media->delete();
    }

    /**
     * @return Collection<int, Media>
     */
    public function getForModel(Model $mediable): Collection
    {
        return Media::query()
            ->where('mediable_type', $mediable->getMorphClass())
            ->where('mediable_id', $mediable->getKey())
            ->whereNotNull('uploaded_at')
            ->orderBy('order_column')
            ->get();
    }

    public function cleanupOrphans(int $olderThanHours = 24): int
    {
        $cutoff = now()->subHours($olderThanHours);

        /** @var Collection<int, Media> $orphans */
        $orphans = Media::query()
            ->whereNull('uploaded_at')
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($orphans as $orphan) {
            /** @var Media $orphan */
            $orphan->delete();
        }

        return $orphans->count();
    }
}
