<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Parent;

use App\Models\Subscription;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Rebing\GraphQL\Support\Facades\GraphQL;
use App\GraphQL\Queries\ParentAuthedQuery;
use App\Services\SubscriptionService;

class SubscriptionsQuery extends ParentAuthedQuery
{
    protected $attributes = [
        'name' => 'subscriptions',
        'description' => 'Returns all subscription plans currently available for purchase, sorted by price ascending.',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('Subscription'))));
    }

    public function resolve($root, array $args): Collection
    {
        return Cache::remember(SubscriptionService::AVAILABLE_CACHE_KEY, now()->addHour(), static function (): Collection {
            return Subscription::query()
                ->where('is_available', true)
                ->orderBy('price')
                ->get();
        });
    }
}
