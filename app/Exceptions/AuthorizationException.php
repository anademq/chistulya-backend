<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Enums\ErrorCode;
use Throwable;

class AuthorizationException extends AppException
{
    public function __construct(string $message = '', array $extensions = [], ?Throwable $previous = null)
    {
        parent::__construct(ErrorCode::FORBIDDEN, $message, $extensions, $previous);
    }

    public static function forbidden(): self
    {
        return new self((string) __('errors.messages.authorization.forbidden'));
    }

    public static function emailNotVerified(): self
    {
        return new self(
            (string) __('errors.messages.authorization.email_not_verified'),
            ['reason' => 'email_not_verified'],
        );
    }

    public static function profileNotCompleted(): self
    {
        return new self(
            (string) __('errors.messages.authorization.profile_not_completed'),
            ['reason' => 'profile_not_completed'],
        );
    }

    public static function onlyChild(): self
    {
        return new self(
            (string) __('errors.messages.authorization.only_child'),
            ['reason' => 'only_child'],
        );
    }

    public static function onlyParent(): self
    {
        return new self(
            (string) __('errors.messages.authorization.only_parent'),
            ['reason' => 'only_parent'],
        );
    }
}
