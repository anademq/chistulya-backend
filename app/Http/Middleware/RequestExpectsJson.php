<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures HTTP clients receive JSON error bodies (e.g. 429) instead of HTML
 * when they omit an Accept header compatible with {@see Request::wantsJson()}.
 */
class RequestExpectsJson
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->wantsJson() && !$request->ajax()) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
