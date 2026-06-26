<?php

use App\Http\Middleware\AuthenticateWithJwt;
use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\EnsureUserProfileRole;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\RequestExpectsJson;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        // api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
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
        //
    })->create();
