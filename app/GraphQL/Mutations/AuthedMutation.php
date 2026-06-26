<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

abstract class AuthedMutation extends PayloadMutation
{
    public function __construct()
    {
        $this->withMiddleware('auth.jwt');
    }
}
