<?php

use App\Exceptions\AppException;
use App\GraphQL\Support\ErrorFormatter;
use App\Http\Middleware\AuthenticateWithJwt;
use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\EnsureUserProfileRole;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\RequestExpectsJson;
use GraphQL\Error\Error as GraphQLError;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // api: __DIR__ . '/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.jwt' => AuthenticateWithJwt::class,
            'user.email.verified' => EnsureEmailVerified::class,
            'user.profile.role' => EnsureUserProfileRole::class,
            'user.role' => EnsureUserRole::class,
            'request.expects_json' => RequestExpectsJson::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /**
         * Expected, client-facing failures. They are already translated into a
         * structured GraphQL response by {@see ErrorFormatter},
         * so writing them to the log adds noise without adding information.
         *
         * Covers every subclass: AuthenticationException, AuthorizationException,
         * InvalidActionException, GraphQLThrottleException.
         */
        $exceptions->dontReport([
            AppException::class,
        ]);

        /**
         * Client-safe GraphQL errors (syntax errors, unknown fields, invalid or
         * missing variables) are caused by a malformed request, not by a server
         * fault. Returning false stops the default handler from logging them,
         * while non-client-safe errors keep their normal reporting.
         */
        $exceptions->reportable(function (GraphQLError $e): ?bool {
            return $e->isClientSafe() ? false : null;
        });
    })->create();
