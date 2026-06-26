<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Account;

use App\GraphQL\Middleware\Contracts\HasThrottleMessage;
use App\GraphQL\Mutations\PayloadMutation;
use App\Models\User;
use App\Services\AuthTokenService;
use App\Services\CaptchaService;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;

class RegisterMutation extends PayloadMutation implements HasThrottleMessage
{
    public function throttleMessage(): string
    {
        return (string) __('errors.messages.throttle.register');
    }

    public function __construct()
    {
        $this->withMiddleware('graphql.throttle:3,1');
    }

    protected $attributes = [
        'name' => 'register',
        'description' => 'Creates a new user account and immediately issues an authenticated session. Sends an email verification link to the provided address.',
    ];

    public function type(): Type
    {
        return GraphQL::type('AuthPayload');
    }

    public function args(): array
    {
        return [
            'email' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Email address for the new account. Must be unique.',
            ],
            'password' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Password for the new account (minimum 6 characters).',
            ],
            'captcha_token' => [
                'type' => Type::string(),
                'description' => 'CAPTCHA verification token (required when CAPTCHA is enabled).',
            ],
            'device' => [
                'type' => Type::string(),
                'description' => 'Human-readable device or client name.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'captcha_token' => ['nullable', 'string', 'max:255'],
            'device' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['tokens' => null];
    }

    public function resolve($root, array $args): array
    {
        $ipAddress = (string) request()->ip();

        return $this->wrapPayload(function () use ($args, $ipAddress): array {
            app(CaptchaService::class)->assertValid($args['captcha_token'] ?? null, $ipAddress);

            $user = User::create([
                'email' => Str::lower($args['email']),
                'password' => $args['password'],
            ]);

            $user->sendEmailVerificationNotification();

            $tokens = app(AuthTokenService::class)->issueTokens($user, [
                'device' => $args['device'] ?? null,
                'ip_address' => $ipAddress,
                'user_agent' => request()->userAgent(),
            ]);

            return ['tokens' => $tokens];
        });
    }
}
