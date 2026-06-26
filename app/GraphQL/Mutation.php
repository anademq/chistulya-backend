<?php

declare(strict_types=1);

namespace App\GraphQL;

use App\GraphQL\Middleware\Concerns\ResolvesParameterizedMiddleware;
use Rebing\GraphQL\Support\Mutation as RebingMutation;

abstract class Mutation extends RebingMutation
{
    use ResolvesParameterizedMiddleware;
}
