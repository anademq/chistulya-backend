<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\Subscription;
use App\Models\User;
use App\Models\User\UserSubscription;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminGrantSubscriptionMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'grantUserSubscription',
        'description' => 'Admin: grant a subscription plan to a user without payment. Optionally specify a custom expiry date.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'user_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the user to grant the subscription to.',
            ],
            'subscription_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the subscription plan.',
            ],
            'expires_at' => [
                'type' => Type::string(),
                'description' => 'Custom expiry date (Y-m-d). Defaults to plan duration from now.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'subscription_id' => ['required', 'uuid', 'exists:subscriptions,id'],
            'expires_at' => ['nullable', 'date_format:Y-m-d', 'after:today'],
        ];
    }

    protected function emptyPayload(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $user = User::whereKey($args['user_id'])->firstOrFail();
            $subscription = Subscription::whereKey($args['subscription_id'])->firstOrFail();

            if (! $user->isRegularUser()) {
                throw ValidationException::withMessages([
                    'user_id' => __('validation.custom.subscription.regular_user_required'),
                ]);
            }

            $expiresAt = isset($args['expires_at'])
                ? now()->parse($args['expires_at'])->endOfDay()
                : now()->addDays((int) $subscription->duration_days);

            UserSubscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'subscription_id' => $subscription->id,
                    'auto_renew' => false,
                    'started_at' => now(),
                    'expires_at' => $expiresAt,
                ],
            );

            return [];
        });
    }
}
