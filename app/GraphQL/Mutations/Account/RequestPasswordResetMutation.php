<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Account;

use App\Exceptions\GraphQLThrottleException;
use App\GraphQL\Middleware\Contracts\HasThrottleMessage;
use App\GraphQL\Mutations\PayloadMutation;
use App\Models\User;
use App\Notifications\ActionMailNotification;
use App\Services\VerificationTokenService;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;

class RequestPasswordResetMutation extends PayloadMutation implements HasThrottleMessage
{
    public function throttleMessage(): string
    {
        return (string) __('errors.messages.throttle.request_password_reset');
    }

    public function __construct()
    {
        $this->withMiddleware('graphql.throttle:3,1');
    }

    protected $attributes = [
        'name' => 'requestPasswordReset',
        'description' => 'Request a password reset link. Always succeeds if the email address is valid, regardless of whether it is registered.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'email' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Email address to send the reset link to.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'email' => ['required', 'email:rfc,dns', 'max:255'],
        ];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $email = Str::lower((string) $args['email']);
            $throttleKey = 'password-reset-request:' . $email;

            if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
                throw new GraphQLThrottleException(
                    (string) __('errors.messages.rate_limited'),
                    RateLimiter::availableIn($throttleKey),
                );
            }

            RateLimiter::hit($throttleKey, 60);

            $user = User::where('email', $email)->first();

            if ($user instanceof User) {
                $resetToken = app(VerificationTokenService::class)->issuePasswordReset($user);

                $url = rtrim((string) config('app.frontend_url'), '/')
                    . config('app.frontend_routes.reset_password')
                    . '?token=' . $resetToken;

                $user->notify(new ActionMailNotification(
                    subject: __('auth.notifications.reset_password.subject'),
                    greeting: __('mail.greeting'),
                    line: __('auth.notifications.reset_password.line'),
                    actionText: __('auth.notifications.reset_password.action'),
                    actionUrl: $url,
                ));
            }

            return [];
        });
    }
}
