<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Account;

use App\GraphQL\Middleware\Contracts\HasThrottleMessage;
use App\GraphQL\Mutations\PayloadMutation;
use App\Services\VerificationTokenService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class VerifyEmailMutation extends PayloadMutation implements HasThrottleMessage
{
    public function throttleMessage(): string
    {
        return (string) __('errors.messages.throttle.verify_email');
    }

    public function __construct()
    {
        $this->withMiddleware('graphql.throttle:5,1');
    }

    protected $attributes = [
        'name' => 'verifyEmail',
        'description' => 'Marks the user\'s email address as verified by consuming the one-time token sent via requestEmailVerification.',
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
                'description' => 'One-time verification token from the email link.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
        ];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $token = app(VerificationTokenService::class)->consumeEmailVerification($args['token']);
            $token->user->update(['email_verified_at' => now()]);

            return [];
        });
    }
}
