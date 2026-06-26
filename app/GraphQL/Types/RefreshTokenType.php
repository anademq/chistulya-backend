<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class RefreshTokenType extends GraphQLType
{
    protected $attributes = [
        'name' => 'RefreshToken',
        'description' => 'Long-lived one-time refresh token with its expiry timestamp.',
    ];

    public function fields(): array
    {
        return [
            'token' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'One-time opaque token value. Consumed on use; a new token is issued in response.',
            ],
            'expires_at' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'ISO 8601 expiration timestamp of the refresh token.',
            ],
        ];
    }
}
