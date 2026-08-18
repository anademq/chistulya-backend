<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

trait SeedsPlaceholderMedia
{
    private const PLACEHOLDER_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAFUlEQVR42mNk+M9Qz0AEYBxVSF+FABJADveWkH6oAAAAAElFTkSuQmCC';

    private const PLACEHOLDER_MIME_TYPE = 'image/png';

    /**
     * Create placeholder media through the regular upload pipeline.
     *
     * The file is written to the temporary prefix exactly like a client upload
     * would be, then handed to {@see MediaService::attachToEntity()}, which
     * validates it, moves it into the permanent media prefix and fills in the
     * real metadata. Seeded media is therefore indistinguishable from media
     * uploaded by a user.
     */
    protected function seedPlaceholderMedia(Model $entity, string $fileName = 'placeholder.png'): Media
    {
        $contents = base64_decode(self::PLACEHOLDER_PNG, true);

        if ($contents === false) {
            throw new RuntimeException('Unable to decode placeholder image.');
        }

        $path = config('media.tmp_prefix').Str::uuid7();

        // The temporary key carries no extension, so the content type has to be
        // set explicitly for S3 to report it back to MediaService::attachToEntity().
        Storage::disk('s3')->put($path, $contents, ['ContentType' => self::PLACEHOLDER_MIME_TYPE]);

        $media = Media::create([
            'disk' => 's3',
            'path' => $path,
            'file_name' => $fileName,
            'mime_type' => self::PLACEHOLDER_MIME_TYPE,
        ]);

        return app(MediaService::class)->attachToEntity($media, $entity);
    }

    protected function syncPlaceholderMedia(Model $entity, string $fileName = 'placeholder.png'): void
    {
        if ($entity->media()->exists()) {
            return;
        }

        $this->seedPlaceholderMedia($entity, $fileName);
    }
}
