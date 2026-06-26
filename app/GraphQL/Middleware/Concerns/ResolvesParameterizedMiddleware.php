<?php

declare(strict_types=1);

namespace App\GraphQL\Middleware\Concerns;

use App\GraphQL\Middleware\ParameterizedMiddleware;

/**
 * Overrides getMiddleware() to pre-resolve alias strings with parameters,
 * and provides withMiddleware() for constructor-based middleware composition.
 *
 * Converts entries like 'user.role:admin,sudo_admin' into ParameterizedMiddleware
 * objects before the pipeline runs, so Rebing's terminate loop never sees raw
 * parameterized strings and doesn't attempt app()->make('user.role:admin').
 */
trait ResolvesParameterizedMiddleware
{
    protected function withMiddleware(string ...$middleware): void
    {
        $this->middleware = array_values(array_unique(array_merge($this->middleware ?? [], $middleware)));
    }

    public function getMiddleware(): array
    {
        return array_map(function (mixed $item): mixed {
            if (! is_string($item)) {
                return $item;
            }

            [$alias, $paramStr] = array_pad(explode(':', $item, 2), 2, '');

            $instance = app()->make($alias);

            $params = $paramStr !== '' ? explode(',', $paramStr) : [];

            if ($params === []) {
                return $instance;
            }

            return new ParameterizedMiddleware($instance, $params);
        }, $this->middleware ?? []);
    }
}
