<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Enums\ErrorCode;
use Throwable;

class GraphQLThrottleException extends AppException
{
    public function __construct(string $message = '', int $retryAfter = 0, ?Throwable $previous = null)
    {
        parent::__construct(
            ErrorCode::RATE_LIMITED,
            $message,
            [
                'retry_after' => max(0, $retryAfter)
            ],
            $previous
        );
    }

    public function retryAfter(): int
    {
        $extensions = $this->extensions() ?? [];

        return (int) ($extensions['retry_after'] ?? 0);
    }
}
