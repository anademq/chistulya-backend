<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\GraphQL\Mutations\PayloadMutation;

abstract class AdminMutation extends PayloadMutation
{
    public function __construct()
    {
        $this->withMiddleware('auth.jwt', 'user.email.verified', 'user.role:admin,sudo_admin');
    }
}
