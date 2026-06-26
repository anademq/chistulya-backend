<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\GraphQL\Query;

abstract class AuthedQuery extends Query
{
    public function __construct()
    {
        $this->withMiddleware('auth.jwt');
    }
}
