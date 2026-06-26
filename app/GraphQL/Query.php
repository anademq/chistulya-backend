<?php

declare(strict_types=1);

namespace App\GraphQL;

use App\GraphQL\Middleware\Concerns\ResolvesParameterizedMiddleware;
use Rebing\GraphQL\Support\Query as RebingQuery;

abstract class Query extends RebingQuery
{
    use ResolvesParameterizedMiddleware;
}
