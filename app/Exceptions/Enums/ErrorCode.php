<?php

declare(strict_types=1);

namespace App\Exceptions\Enums;

enum ErrorCode: string
{
    case UNAUTHENTICATED = 'UNAUTHENTICATED';
    case FORBIDDEN = 'FORBIDDEN';
    case INVALID_ACTION = 'INVALID_ACTION';
    case VALIDATION = 'VALIDATION';
    case RATE_LIMITED = 'RATE_LIMITED';
    case BAD_REQUEST = 'BAD_REQUEST';
    case INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
}
