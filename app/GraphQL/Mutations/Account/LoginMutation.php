<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Account;

use App\Exceptions\InvalidActionException;
use App\GraphQL\Middleware\Contracts\HasThrottleMessage;
use App\GraphQL\Mutations\PayloadMutation;
use App\Models\User;
use App\Services\AuthTokenService;
use App\Services\CaptchaService;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginMutation extends PayloadMutation implements HasThrottleMessage
{
    public function throttleMessage(): string
    {
        return (string) __('errors.messages.throttle.login');
    }

    public function __construct()
    {
        $this->withMiddleware('graphql.throttle:3,1');
    }

    protected $attributes = [
        'name' => 'login',
        'description' => 'Authenticates a user by email and password and returns a new JWT session.',
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
                'description' => 'Email address of the account.',
            ],
            'password' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Account password.',
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
            'email' => ['required', 'email:rfc', 'max:255'],
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
            $this->guardAlreadyLoggedIn();

            app(CaptchaService::class)->assertValid($args['captcha_token'] ?? null, $ipAddress);

            /** @var User|null $user */
            $user = User::where('email', $args['email'])->first();

            if (!$user || !Hash::check($args['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => __('validation.custom.auth.login_invalid'),
                ]);
            }

            $tokens = app(AuthTokenService::class)->issueTokens($user, [
                'device' => $args['device'] ?? null,
                'ip_address' => $ipAddress,
                'user_agent' => request()->userAgent(),
            ]);

            return ['tokens' => $tokens];
        });
    }

    private function guardAlreadyLoggedIn(): void
    {
        if (blank(request()->bearerToken())) {
            return;
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
            $payload = JWTAuth::parseToken()->getPayload();
        } catch (TokenExpiredException | JWTException | \Throwable) {
            return;
        }

        if (!$user instanceof User) {
            return;
        }

        $sessionId = (string) $payload->get('sid');
        if (blank($sessionId)) {
            return;
        }

        /** @var \App\Models\Auth\Session|null $session */
        $session = $user->sessions()
            ->whereKey($sessionId)
            ->whereNull('revoked_at')
            ->first();

        if (!$session) {
            return;
        }

        $isActiveSession = $session->refreshTokens()
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($isActiveSession) {
            throw new InvalidActionException((string) __('validation.custom.auth.already_logged_in'));
        }
    }
}
