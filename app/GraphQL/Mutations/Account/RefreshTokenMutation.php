<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Account;

use App\GraphQL\Middleware\Contracts\HasThrottleMessage;
use App\GraphQL\Mutations\PayloadMutation;
use App\Services\AuthTokenService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class RefreshTokenMutation extends PayloadMutation implements HasThrottleMessage
{
    public function throttleMessage(): string
    {
        return (string) __('errors.messages.throttle.refresh_token');
    }

    public function __construct()
    {
        $this->withMiddleware('graphql.throttle:10,1');
    }

    protected $attributes = [
        'name' => 'refreshToken',
        'description' => 'Exchanges a valid one-time refresh token for a new JWT access/refresh token pair.',
    ];

    public function type(): Type
    {
        return GraphQL::type('AuthPayload');
    }

    public function args(): array
    {
        return [
            'refresh_token' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'One-time refresh token value received from login or a previous refreshToken call.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'refresh_token' => ['required', 'string', 'min:64'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['tokens' => null];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $tokens = app(AuthTokenService::class)->refreshAccessToken($args['refresh_token']);

            return ['tokens' => $tokens];
        });
    }
}
