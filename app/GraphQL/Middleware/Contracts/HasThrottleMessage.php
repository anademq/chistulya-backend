<?php

declare(strict_types=1);

namespace App\GraphQL\Middleware\Contracts;

interface HasThrottleMessage
{
    public function throttleMessage(): string;
}
