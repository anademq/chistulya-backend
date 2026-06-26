<?php

declare(strict_types=1);

namespace App\Http\Middleware\Contracts;

use Closure;

interface GraphQLResolvable
{
    /**
     * @see Field::getResolver()  Middleware is resolved in the field resolver pipeline
     */
    public function resolve(array $arguments, Closure $next, ...$params): mixed;
}
