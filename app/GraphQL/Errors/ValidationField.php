<?php

declare(strict_types=1);

namespace App\GraphQL\Errors;

final readonly class ValidationField
{
    /** @param list<string> $messages */
    public function __construct(
        public string $field,
        public array $messages,
    ) {}
}
