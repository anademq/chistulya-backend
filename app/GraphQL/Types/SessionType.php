<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Auth\Session;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class SessionType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Session',
        'description' => 'An active authentication session associated with a user.',
        'model' => Session::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the session. Used in the logout mutation to revoke a specific session.',
            ],
            'device' => [
                'type' => Type::string(),
                'description' => 'Human-readable device or client name supplied at login. Null if not provided.',
            ],
            'ip_address' => [
                'type' => Type::string(),
                'description' => 'IP address from which the session was created. Null if unavailable.',
            ],
            'user_agent' => [
                'type' => Type::string(),
                'description' => 'User-Agent header string from the login request. Null if unavailable.',
            ],
            'last_seen_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp of the last activity on this session. Null if never updated.',
                'resolve' => static fn(Session $root): ?string => $root->last_seen_at?->toDateTimeString(),
            ],
        ];
    }
}
