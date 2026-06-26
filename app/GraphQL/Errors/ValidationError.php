<?php

declare(strict_types=1);

namespace App\GraphQL\Errors;

final readonly class ValidationError
{
    /** @param list<ValidationField> $fields */
    public function __construct(
        public string $message,
        public array $fields,
    ) {}
}
