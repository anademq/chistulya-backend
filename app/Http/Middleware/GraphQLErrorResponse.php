<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When the GraphQL executor raises top-level errors (unhandled exceptions,
 * schema violations, etc.), strip the `data` key from the response so the
 * client receives only `{"errors": [...]}` instead of `{"data": {...}, "errors": [...]}`.
 *
 * Payload-level errors (success/errors inside a mutation result) are unaffected
 * because they do not produce top-level GraphQL errors.
 */
class GraphQLErrorResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse) {
            return $response;
        }

        $body = $response->getData(assoc: true);

        if (! is_array($body) || empty($body['errors'])) {
            return $response;
        }

        unset($body['data']);

        return $response->setData($body);
    }
}
