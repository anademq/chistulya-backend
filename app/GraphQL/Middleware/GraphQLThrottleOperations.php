<?php

declare(strict_types=1);

namespace App\GraphQL\Middleware;

use App\Exceptions\GraphQLThrottleException;
use App\GraphQL\Middleware\Contracts\HasThrottleMessage;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;

class GraphQLThrottleOperations
{
    /**
     * @see Field::getResolver()  Middleware is resolved in the field resolver pipeline
     */
    public function resolve(array $arguments, Closure $next, ...$params)
    {
        return $this->handle(
            ...$arguments,
            ...[
                function (...$arguments) use ($next) {
                    return $next($arguments);
                },
            ],
            ...$params
        );
    }

    /**
     * @param array<string,mixed> $args
     */
    public function handle($root, array $args, $context, ResolveInfo $info, Closure $next, string $maxAttempts = '60', string $decayMinutes = '1')
    {
        if (!is_numeric($maxAttempts) || !is_numeric($decayMinutes) || (int) $maxAttempts <= 0 || (int) $decayMinutes <= 0) {
            throw new InvalidArgumentException('[graphql.throttle] middleware requires two positive integers: maxAttempts and decayMinutes.');
        }

        $operationName = $info->fieldName;

        $user = auth()->user();
        $identifier = $user ? (string) $user->getKey() : (string) request()->ip();

        $key = "graphql:throttle:{$operationName}:{$identifier}";
        $max = (int) $maxAttempts;
        $decaySeconds = (int) $decayMinutes * 60;

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $retryAfter = RateLimiter::availableIn($key);

            $errorMessage = $root instanceof HasThrottleMessage
                ? $root->throttleMessage()
                : (string) __('errors.messages.rate_limited');

            throw new GraphQLThrottleException($errorMessage, $retryAfter);
        }

        RateLimiter::hit($key, $decaySeconds);

        return $next($root, $args, $context, $info);
    }
}
