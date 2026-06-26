<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use GraphQL\Type\Definition\Type;

class RequestMediaUploadPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'RequestMediaUploadPayload',
        'description' => 'Payload for the requestMediaUpload mutation.',
    ];

    protected function payloadFields(): array
    {
        return [
            'media_id' => [
                'type' => Type::string(),
                'description' => 'UUID of the created pending media record. Pass to confirmMediaUpload or an entity mutation to finalize.',
            ],
            'upload_url' => [
                'type' => Type::string(),
                'description' => 'Pre-signed S3 URL. PUT the file bytes directly to this URL.',
            ],
        ];
    }
}
