<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ProfileRole;
use App\Exceptions\AuthenticationException;
use App\Exceptions\AuthorizationException;
use App\Http\Middleware\Contracts\GraphQLResolvable;
use App\Models\Profile;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserProfileRole implements GraphQLResolvable
{
    public function resolve(array $arguments, Closure $next, ...$params): mixed
    {
        $this->ensureUserProfileRole($params);
        return $next($arguments);
    }

    public function handle(Request $request, Closure $next, ...$params): Response
    {
        $this->ensureUserProfileRole($params);
        return $next($request);
    }

    protected function ensureUserProfileRole(array $params): void
    {
        $role = array_shift($params);

        if ($role) {

            $enumRole = ProfileRole::tryFrom($role);

            if (!$enumRole) {
                throw new InvalidArgumentException("Unsupported [user.profile.role] guard [{$role}].");
            }
        }

        $user = auth()->user();
        if (!$user) {
            throw AuthenticationException::required();
        }

        /** @var Profile|null */
        $profile = $user->profile;

        if (!$profile) {
            throw AuthorizationException::profileNotCompleted();
        }

        if ($role && $enumRole !== $profile->role) {
            throw match ($enumRole) {
                ProfileRole::PARENT => AuthorizationException::onlyParent(),
                ProfileRole::CHILD => AuthorizationException::onlyChild(),
                default => AuthorizationException::forbidden(),
            };
        }
    }
}
