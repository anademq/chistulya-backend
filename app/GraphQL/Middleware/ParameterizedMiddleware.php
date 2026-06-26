<?php

declare(strict_types=1);

namespace App\GraphQL\Middleware;

use Closure;

/**
 * Wraps a middleware instance with pre-parsed parameters.
 *
 * Rebing's terminate loop calls app()->make($rawString) on string middleware entries,
 * which breaks for parameterized aliases like 'user.role:admin'. By pre-resolving the
 * alias and wrapping it in this object, the pipeline receives an object (not a string),
 * and the terminate loop skips it entirely.
 */
final class ParameterizedMiddleware
{
    public function __construct(
        protected readonly object $middleware,
        protected readonly array $params = [],
    ) {
    }

    public function resolve(array $arguments, Closure $next): mixed
    {
        return $this->middleware->resolve($arguments, $next, ...$this->params);
    }

    public function terminate(mixed ...$args): void
    {
        if (method_exists($this->middleware, 'terminate')) {
            $this->middleware->terminate(...$args);
        }
    }
}
