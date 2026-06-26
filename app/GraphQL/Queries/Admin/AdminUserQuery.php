<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Admin;

use App\Models\User;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class AdminUserQuery extends AdminQuery
{
    protected $attributes = [
        'name' => 'user',
        'description' => 'Returns a single user with all related data for the admin panel.',
    ];

    public function type(): Type
    {
        return GraphQL::type('User');
    }

    public function args(): array
    {
        return [
            'user_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the user to fetch.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }

    public function resolve($root, array $args): User
    {
        return User::with([
            'profile',
            'exp',
            'wallet',
            'parents.profile',
            'children.profile',
            'userSubscription',
        ])->whereKey($args['user_id'])->firstOrFail();
    }
}
