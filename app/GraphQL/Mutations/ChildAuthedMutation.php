<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

abstract class ChildAuthedMutation extends AuthedMutation
{
    public function __construct()
    {
        parent::__construct();
        $this->withMiddleware('user.email.verified', 'user.profile.role:child');
    }
}
