<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Enums\ErrorCode;
use Throwable;

class AuthenticationException extends AppException
{
    public function __construct(string $message = '', array $extensions = [], ?Throwable $previous = null)
    {
        parent::__construct(ErrorCode::UNAUTHENTICATED, $message, $extensions, $previous);
    }

    public static function required(): self
    {
        return new self((string) __('errors.messages.authentication.required'));
    }

    public static function tokenInvalid(): self
    {
        return new self(
            (string) __('errors.messages.authentication.token_invalid'),
            ['reason' => 'token_invalid'],
        );
    }

    public static function tokenExpired(): self
    {
        return new self(
            (string) __('errors.messages.authentication.token_expired'),
            ['reason' => 'token_expired'],
        );
    }

    public static function sessionInactive(): self
    {
        return new self(
            (string) __('errors.messages.authentication.session_inactive'),
            ['reason' => 'session_inactive'],
        );
    }
}
