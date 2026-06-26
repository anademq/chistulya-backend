<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Media;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class MediaType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Media',
        'description' => 'An uploaded media file record.',
        'model' => Media::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Unique UUID identifier of the media record.',
            ],
            'file_name' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Original file name provided at upload time.',
            ],
            'mime_type' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'MIME type of the file (e.g. "image/jpeg").',
            ],
            'size' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'File size in bytes.',
            ],
            'url' => [
                'type' => Type::string(),
                'description' => 'Public URL to access the file. Null if not yet uploaded.',
                'resolve' => static fn(Media $media): ?string => $media->isUploaded() ? $media->url() : null,
            ],
            'uploaded_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the file was confirmed uploaded. Null if pending.',
            ],
            'created_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the upload was requested.',
            ],
        ];
    }
}
