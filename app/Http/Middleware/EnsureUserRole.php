<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Exceptions\AuthenticationException;
use App\Exceptions\AuthorizationException;
use App\Http\Middleware\Contracts\GraphQLResolvable;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole implements GraphQLResolvable
{
    public function resolve(array $arguments, Closure $next, ...$params): mixed
    {
        $this->ensureUserRole($params);
        return $next($arguments);
    }

    public function handle(Request $request, Closure $next, ...$params): Response
    {
        $this->ensureUserRole($params);
        return $next($request);
    }

    protected function ensureUserRole(array $params): void
    {
        if (empty($params)) {
            throw new InvalidArgumentException('At least one role must be specified for [user.role] middleware.');
        }

        $enumRoles = [];

        foreach ($params as $role) {

            $enumRole = UserRole::tryFrom($role);

            if (!$enumRole) {
                throw new InvalidArgumentException("Unsupported [user.role] guard [{$role}].");
            }

            $enumRoles[] = $enumRole;
        }

        $user = auth()->user();
        if (!$user) {
            throw AuthenticationException::required();
        }

        if (!in_array($user->role, $enumRoles, true)) {
            throw AuthorizationException::forbidden();
        }
    }
}
