<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class UserPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'UserPayload',
        'description' => 'Payload for admin user creation and update mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'user' => [
                'type' => GraphQL::type('User'),
                'description' => 'The created or updated user.',
            ],
        ];
    }
}
