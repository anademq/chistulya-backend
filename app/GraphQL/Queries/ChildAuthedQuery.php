<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

abstract class ChildAuthedQuery extends AuthedQuery
{
    public function __construct()
    {
        parent::__construct();
        $this->withMiddleware('user.email.verified', 'user.profile.role:child');
    }
}
