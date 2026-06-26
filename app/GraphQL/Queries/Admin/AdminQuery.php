<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Admin;

use App\GraphQL\Query;

abstract class AdminQuery extends Query
{
    public function __construct()
    {
        $this->withMiddleware('auth.jwt', 'user.email.verified', 'user.role:admin,sudo_admin');
    }
}
