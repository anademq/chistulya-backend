<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Enums\ErrorCode;
use RuntimeException;
use Throwable;

abstract class AppException extends RuntimeException
{
    public function __construct(
        private readonly ErrorCode $errorCode,
        string $message = '',
        private readonly array $extensions = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function errorCode(): ErrorCode
    {
        return $this->errorCode;
    }

    public function extensions(): array
    {
        return $this->extensions;
    }

    public function getExtensions(): array
    {
        return ['code' => $this->errorCode()->value, ...$this->extensions()];
    }
}
