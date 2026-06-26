<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\AuthenticationException;
use App\Http\Middleware\Contracts\GraphQLResolvable;
use App\Models\Auth\Session;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWTAuth;

class AuthenticateWithJwt implements GraphQLResolvable
{
    public function __construct(
        private readonly JWTAuth $jwtAuth,
    ) {
    }

    public function resolve(array $arguments, Closure $next, ...$params): mixed
    {
        $this->authenticate();
        return $next($arguments);
    }

    public function handle(Request $request, Closure $next, ...$params): Response
    {
        $this->authenticate();
        return $next($request);
    }

    protected function authenticate(): void
    {
        try {
            $this->jwtAuth->parseToken();
        } catch (JWTException) {
            throw AuthenticationException::required();
        }

        try {
            /** @var User */
            $user = $this->jwtAuth->authenticate();
            $payload = $this->jwtAuth->getPayload();
        } catch (TokenInvalidException) {
            throw AuthenticationException::tokenInvalid();
        } catch (TokenExpiredException) {
            throw AuthenticationException::tokenExpired();
        } catch (JWTException) {
            throw AuthenticationException::required();
        }

        $sessionId = (string) $payload->get('sid');

        if (blank($sessionId)) {
            throw AuthenticationException::tokenInvalid();
        }

        /** @var Session|null */
        $session = $user->sessions()->find($sessionId);

        if (
            !$session
            || $session->isRevoked()
            || !$session->refreshTokens()
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->exists()
        ) {
            throw AuthenticationException::sessionInactive();
        }

        auth('api')->setUser($user);
        auth()->setUser($user);
    }
}
