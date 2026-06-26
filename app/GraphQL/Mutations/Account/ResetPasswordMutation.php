<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Account;

use App\GraphQL\Mutations\PayloadMutation;
use App\Services\AuthTokenService;
use App\Services\VerificationTokenService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ResetPasswordMutation extends PayloadMutation
{
    protected $attributes = [
        'name' => 'resetPassword',
        'description' => 'Reset password by one-time reset token.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'token' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'One-time reset token.',
            ],
            'password' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'New password.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $token = app(VerificationTokenService::class)->consumePasswordReset($args['token']);
            $user = $token->user;
            $user->update(['password' => $args['password']]);

            app(AuthTokenService::class)->revokeSessions($user);

            return [];
        });
    }
}
