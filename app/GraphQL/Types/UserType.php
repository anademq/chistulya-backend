<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\User;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class UserType extends GraphQLType
{
    protected $attributes = [
        'name' => 'User',
        'description' => 'Represents a registered user account.',
        'model' => User::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Unique UUID identifier of the user.',
            ],
            'email' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Email address used for authentication.',
            ],
            'role' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'System role value (e.g. "user", "admin").',
                'resolve' => static fn(User $user): string => $user->role->value,
            ],
            'email_verified_at' => [
                'type' => Type::string(),
                'description' => 'Timestamp when the email was verified. Null if not yet verified.',
            ],
            'profile' => [
                'type' => GraphQL::type('Profile'),
                'description' => 'User profile data. Null if no profile has been created yet.',
            ],
            'wallet' => [
                'type' => GraphQL::type('Wallet'),
                'description' => 'Coin wallet for child users. Null for non-child users.',
            ],
            'exp' => [
                'type' => GraphQL::type('Exp'),
                'description' => 'Experience and level data for child users. Null for non-child users.',
            ],
            'parents' => [
                'type' => Type::listOf(GraphQL::type('User')),
                'description' => 'Linked parent profiles. Present only on child accounts.',
            ],
            'children' => [
                'type' => Type::listOf(GraphQL::type('User')),
                'description' => 'Linked child profiles. Present only on parent accounts.',
            ],
            'created_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the account was created.',
            ],
        ];
    }
}
