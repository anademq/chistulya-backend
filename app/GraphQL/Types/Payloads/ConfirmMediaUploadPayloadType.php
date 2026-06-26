<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class ConfirmMediaUploadPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'ConfirmMediaUploadPayload',
        'description' => 'Payload for the confirmMediaUpload mutation.',
    ];

    protected function payloadFields(): array
    {
        return [
            'media' => [
                'type' => GraphQL::type('Media'),
                'description' => 'The confirmed media record with public URL. Null when errors occurred.',
            ],
        ];
    }
}
