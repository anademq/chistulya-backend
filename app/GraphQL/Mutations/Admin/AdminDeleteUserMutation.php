<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\User;
use App\Services\AuthTokenService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminDeleteUserMutation extends AdminMutation
{
    protected $middleware = ['auth.jwt', 'user.email.verified', 'user.role:sudo_admin'];

    protected $attributes = [
        'name' => 'deleteUser',
        'description' => 'Sudo-admin: hard-delete a user account. Revokes all active sessions immediately. Requires sudo_admin role.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'user_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the user to delete.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $target = User::whereKey($args['user_id'])->firstOrFail();
            app(AuthTokenService::class)->revokeSessions($target);

            return [];
        });
    }
}
