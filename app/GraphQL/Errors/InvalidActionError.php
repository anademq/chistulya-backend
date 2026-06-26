<?php

declare(strict_types=1);

namespace App\GraphQL\Errors;

final readonly class InvalidActionError
{
    public function __construct(
        public string $message,
    ) {}
}
