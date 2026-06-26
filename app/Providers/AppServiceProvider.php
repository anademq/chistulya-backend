<?php

namespace App\Providers;

use App\Http\Middleware\AuthenticateWithJwt;
use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\EnsureUserProfileRole;
use App\Http\Middleware\EnsureUserRole;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('auth.jwt', static fn($app) => $app->make(AuthenticateWithJwt::class));
        $this->app->bind('user.email.verified', static fn($app) => $app->make(EnsureEmailVerified::class));
        $this->app->bind('user.profile.role', static fn($app) => $app->make(EnsureUserProfileRole::class));
        $this->app->bind('user.role', static fn($app) => $app->make(EnsureUserRole::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('graphql', static function (Request $request): Limit {
            return Limit::perSecond(5)->by((string) $request->ip());
        });

        Broadcast::routes(['middleware' => ['auth.jwt']]);
    }
}
