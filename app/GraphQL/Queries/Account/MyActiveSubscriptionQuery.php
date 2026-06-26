<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Account;

use App\GraphQL\Queries\AuthedQuery;
use App\Models\User\UserSubscription;
use App\Services\SubscriptionService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class MyActiveSubscriptionQuery extends AuthedQuery
{
    protected $attributes = [
        'name' => 'myActiveSubscription',
        'description' => 'Returns the currently active subscription for the authenticated user, or null if none exists.',
    ];

    public function type(): Type
    {
        return GraphQL::type('UserSubscription');
    }

    public function resolve($root, array $args): ?UserSubscription
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return app(SubscriptionService::class)->effectiveActiveSubscription($user);
    }
}
