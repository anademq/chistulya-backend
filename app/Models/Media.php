<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'media';

    protected $fillable = [
        'created_by',
        'disk',
        'path',
        'file_name',
        'mime_type',
        'size',
        'mediable_id',
        'mediable_type',
        'uploaded_at',
        'order_column',
    ];

    protected $casts = [
        'size' => 'integer',
        'order_column' => 'integer',
        'uploaded_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isUploaded(): bool
    {
        return (bool) $this->uploaded_at;
    }

    /**
     * Returns a signed temporary URL for the file (valid for 60 minutes by default).
     */
    public function url(int $minutes = 60): string
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk);

        return $disk->temporaryUrl($this->path, now()->addMinutes($minutes));
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('uploaded_at');
    }

    public function delete(): bool
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk);

        if ($disk->exists($this->path)) {
            $disk->delete($this->path);
        }

        return parent::delete();
    }
}
