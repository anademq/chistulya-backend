<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Cache;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminDeleteSubscriptionMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'deleteSubscription',
        'description' => 'Admin: soft-delete a subscription plan. Active user subscriptions are not affected.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::string()), 'description' => 'UUID of the subscription plan to delete.'],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:subscriptions,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            Subscription::whereKey($args['id'])->firstOrFail()->delete();

            Cache::forget(SubscriptionService::AVAILABLE_CACHE_KEY);

            return [];
        });
    }
}
