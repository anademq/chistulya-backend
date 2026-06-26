<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Cache;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminCreateSubscriptionMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'createSubscription',
        'description' => 'Admin: create a new subscription plan.',
    ];

    public function type(): Type
    {
        return GraphQL::type('SubscriptionPayload');
    }

    public function args(): array
    {
        return [
            'title' => ['type' => Type::nonNull(Type::string())],
            'short_description' => ['type' => Type::string()],
            'description' => ['type' => Type::string()],
            'is_available' => ['type' => Type::boolean(), 'defaultValue' => false],
            'duration_days' => ['type' => Type::nonNull(Type::int())],
            'price' => ['type' => Type::nonNull(Type::float())],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['subscription' => null];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $subscription = Subscription::create([
                'title' => $args['title'],
                'short_description' => $args['short_description'] ?? null,
                'description' => $args['description'] ?? null,
                'is_available' => (bool) ($args['is_available'] ?? false),
                'duration_days' => (int) $args['duration_days'],
                'price' => (float) $args['price'],
            ]);

            Cache::forget(SubscriptionService::AVAILABLE_CACHE_KEY);

            return ['subscription' => $subscription];
        });
    }
}
