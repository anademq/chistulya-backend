<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\AuthenticationException;
use App\Exceptions\AuthorizationException;
use App\Http\Middleware\Contracts\GraphQLResolvable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerified implements GraphQLResolvable
{
    public function resolve(array $arguments, Closure $next, ...$params): mixed
    {
        $this->ensureEmailVerified();
        return $next($arguments);
    }

    public function handle(Request $request, Closure $next, ...$params): Response
    {
        $this->ensureEmailVerified();
        return $next($request);
    }

    protected function ensureEmailVerified(): void
    {
        $user = auth()->user();

        if (!$user) {
            throw AuthenticationException::required();
        }

        if (!$user->hasVerifiedEmail()) {
            throw AuthorizationException::emailNotVerified();
        }
    }
}
