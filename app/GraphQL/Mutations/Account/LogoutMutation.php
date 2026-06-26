<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Account;

use App\Exceptions\AuthenticationException;
use App\GraphQL\Mutations\AuthedMutation;
use App\Models\User;
use App\Services\AuthTokenService;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Tymon\JWTAuth\Facades\JWTAuth;

class LogoutMutation extends AuthedMutation
{
    protected $attributes = [
        'name' => 'logout',
        'description' => 'Revokes the current or a specific session, invalidating its tokens immediately. Pass "all" to revoke every active session.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'session_id' => [
                'type' => Type::string(),
                'description' => 'UUID of the session to revoke, or "all" to revoke every active session. Omit to revoke the current session.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'session_id' => [
                'nullable',
                'string',
                Rule::anyOf([
                    ['uuid'],
                    ['in:all'],
                ]),
            ],
        ];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            throw AuthenticationException::required();
        }

        return $this->wrapPayload(function () use ($user, $args): array {
            $service = app(AuthTokenService::class);
            $sessionId = str((string) ($args['session_id'] ?? ''))->trim()->toString();

            if (blank($sessionId)) {
                $currentSessionId = (string) JWTAuth::parseToken()->getPayload()->get('sid');

                if (blank($currentSessionId)) {
                    throw AuthenticationException::tokenInvalid();
                }

                $service->revokeSession($user, $currentSessionId);
            } elseif (str($sessionId)->lower()->toString() === 'all') {
                $service->revokeSessions($user);
            } else {
                $service->revokeSession($user, $sessionId);
            }

            return [];
        });
    }
}
