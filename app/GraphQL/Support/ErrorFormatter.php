<?php

declare(strict_types=1);

namespace App\GraphQL\Support;

use App\Exceptions\AppException;
use App\Exceptions\AuthenticationException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\Enums\ErrorCode;
use GraphQL\Error\Error;
use Illuminate\Auth\Access\AuthorizationException as LaravelAuthorizationException;
use Illuminate\Auth\AuthenticationException as LaravelAuthenticationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Rebing\GraphQL\Error\AuthorizationError as RebingAuthorizationError;
use Rebing\GraphQL\Error\ValidationError as RebingValidationError;

/**
 * Wired into config('graphql.error_formatter').
 *
 * Every branch returns ONLY 'message' and 'extensions'
 * that Rebing's default formatter includes is intentionally omitted here.
 *
 * Error routing:
 *   AppException                   → message + extensions.code (always visible to client)
 *   LaravelAuthenticationException → UNAUTHENTICATED
 *   LaravelAuthorizationException  → FORBIDDEN
 *   RebingAuthorizationError       → FORBIDDEN
 *   LaravelValidationException     → VALIDATION + per-field messages
 *   RebingValidationError          → VALIDATION + per-field messages
 *   GraphQL client-safe errors     → BAD_REQUEST (syntax / unknown-field / invalid-variable)
 *   Anything else                  → INTERNAL_SERVER_ERROR + server log
 */
final class ErrorFormatter
{
    /** @return array<string, mixed> */
    public static function format(Error $error): array
    {
        $previous = $error->getPrevious();

        // Map known exception types to AppExceptions
        $appException = match (true) {
            $previous instanceof AppException => $previous,
            $previous instanceof LaravelAuthenticationException => new AuthenticationException($previous->getMessage() ?: ''),
            $previous instanceof LaravelAuthorizationException || $previous instanceof RebingAuthorizationError => new AuthorizationException($previous->getMessage() ?: ''),
            default => null,
        };

        if ($appException !== null) {
            return self::fromAppException($appException);
        }

        // Laravel ValidationException thrown explicitly (e.g. ValidationException::withMessages([...]))
        if ($previous instanceof LaravelValidationException) {
            return self::validationResponse($previous->validator->errors()->getMessages());
        }

        // Rebing rules() validation failure.
        // webonyx wraps thrown errors via Error::createLocatedError(), so the ValidationError
        // may arrive as $error->getPrevious() rather than $error itself.
        $validationError = match (true) {
            $error instanceof RebingValidationError => $error,
            $previous instanceof RebingValidationError => $previous,
            default => null,
        };

        if ($validationError !== null) {
            return self::validationResponse($validationError->getValidatorMessages()->getMessages());
        }

        // GraphQL-level errors: syntax errors, unknown fields, invalid variables
        if ($error->isClientSafe()) {
            return [
                'message' => $error->getMessage(),
                'extensions' => ['code' => ErrorCode::BAD_REQUEST->value],
            ];
        }

        self::logUnhandled($error);

        return [
            'message' => (string) __('errors.messages.internal_server_error'),
            'extensions' => ['code' => ErrorCode::INTERNAL_SERVER_ERROR->value],
        ];
    }

    /** @return array<string, mixed> */
    protected static function fromAppException(AppException $exception): array
    {
        return [
            'message' => $exception->getMessage(),
            'extensions' => $exception->getExtensions(),
        ];
    }

    /**
     * @param  array<string, array<string>>  $messages
     * @return array<string, mixed>
     */
    protected static function validationResponse(array $messages): array
    {
        $fields = [];

        foreach ($messages as $field => $fieldMessages) {
            $fields[] = ['field' => (string) $field, 'messages' => array_values(array_map('strval', (array) $fieldMessages))];
        }

        return [
            'message' => (string) __('errors.messages.validation'),
            'extensions' => [
                'code' => ErrorCode::VALIDATION->value,
                'fields' => $fields,
            ],
        ];
    }

    protected static function logUnhandled(Error $error): void
    {
        $underlying = $error->getPrevious() ?? $error;

        Log::error('Unhandled GraphQL error', [
            'message' => $error->getMessage(),
            'exception' => $underlying::class,
            'path' => $error->getPath(),
            'trace' => $underlying->getTraceAsString(),
        ]);
    }
}
