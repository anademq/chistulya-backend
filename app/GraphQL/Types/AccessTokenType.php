<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class AccessTokenType extends GraphQLType
{
    protected $attributes = [
        'name' => 'AccessToken',
        'description' => 'Short-lived JWT access token with its expiry timestamp.',
    ];

    public function fields(): array
    {
        return [
            'token' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Signed JWT string. Pass as "Authorization: Bearer {token}".',
            ],
            'expires_at' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'ISO 8601 expiration timestamp of the access token.',
            ],
        ];
    }
}
