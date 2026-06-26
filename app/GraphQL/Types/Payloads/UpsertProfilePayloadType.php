<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class UpsertProfilePayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'UpsertProfilePayload',
        'description' => 'Payload for the upsertProfile mutation.',
    ];

    protected function payloadFields(): array
    {
        return [
            'profile' => [
                'type' => GraphQL::type('Profile'),
                'description' => 'The created or updated profile. Null when errors occurred.',
            ],
        ];
    }
}
