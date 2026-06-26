<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class AuthPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'AuthPayload',
        'description' => 'Payload for authentication mutations (register, login, refreshToken).',
    ];

    protected function payloadFields(): array
    {
        return [
            'tokens' => [
                'type' => GraphQL::type('AuthTokens'),
                'description' => 'Token pair on success. Null when errors occurred.',
            ],
        ];
    }
}
