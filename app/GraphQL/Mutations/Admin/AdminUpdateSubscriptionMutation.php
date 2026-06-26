<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Cache;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminUpdateSubscriptionMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'updateSubscription',
        'description' => 'Admin: update an existing subscription plan.',
    ];

    public function type(): Type
    {
        return GraphQL::type('SubscriptionPayload');
    }

    public function args(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::string())],
            'title' => ['type' => Type::string()],
            'short_description' => ['type' => Type::string()],
            'description' => ['type' => Type::string()],
            'is_available' => ['type' => Type::boolean()],
            'duration_days' => ['type' => Type::int()],
            'price' => ['type' => Type::float()],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:subscriptions,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['subscription' => null];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $subscription = Subscription::withTrashed()->whereKey((string) $args['id'])->firstOrFail();

            $fields = array_filter(
                array_intersect_key($args, array_flip(['title', 'short_description', 'description', 'is_available', 'duration_days', 'price'])),
                static fn($v) => $v !== null,
            );

            if (!empty($fields)) {
                $subscription->forceFill($fields)->save();
            }

            Cache::forget(SubscriptionService::AVAILABLE_CACHE_KEY);

            return ['subscription' => $subscription->refresh()];
        });
    }
}
