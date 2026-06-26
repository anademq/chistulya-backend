<?php

declare(strict_types=1);

namespace App\Providers;

use App\GraphQL\Middleware\GraphQLThrottleOperations;
use Illuminate\Support\ServiceProvider;

class GraphQLServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('graphql.throttle', static fn($app) => $app->make(GraphQLThrottleOperations::class));
    }
}
