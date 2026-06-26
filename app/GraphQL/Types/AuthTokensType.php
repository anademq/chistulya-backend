<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class AuthTokensType extends GraphQLType
{
    protected $attributes = [
        'name' => 'AuthTokens',
        'description' => 'Authentication result containing session identifier and JWT token pair.',
    ];

    public function fields(): array
    {
        return [
            'session_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the created session. Pass to logout to revoke a specific session.',
            ],
            'access' => [
                'type' => Type::nonNull(GraphQL::type('AccessToken')),
                'description' => 'Short-lived JWT access token used to authenticate API requests.',
                'resolve' => static fn(array $root): array => [
                    'token' => $root['access_token'],
                    'expires_at' => (string) $root['access_expires_at'],
                ],
            ],
            'refresh' => [
                'type' => Type::nonNull(GraphQL::type('RefreshToken')),
                'description' => 'Long-lived one-time token used to obtain a new access token via refreshToken.',
                'resolve' => static fn(array $root): array => [
                    'token' => $root['refresh_token'],
                    'expires_at' => (string) $root['refresh_expires_at'],
                ],
            ],
        ];
    }
}
