<?php

namespace App\Exceptions;

use App\Exceptions\Enums\ErrorCode;
use Throwable;

class InvalidActionException extends AppException
{
    public function __construct(string $message = '', array $extensions = [], ?Throwable $previous = null)
    {
        parent::__construct(ErrorCode::INVALID_ACTION, $message, $extensions, $previous);
    }
}
