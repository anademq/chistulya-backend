<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\User;
use App\Services\AuthTokenService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminForceLogoutMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'revokeUserSessions',
        'description' => 'Admin: revoke all active sessions for a given user immediately.',
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
                'description' => 'UUID of the user whose sessions should be revoked.',
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
            $user = User::whereKey($args['user_id'])->firstOrFail();
            app(AuthTokenService::class)->revokeSessions($user);

            return [];
        });
    }
}
