<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Parent;

use App\GraphQL\Mutations\ParentAuthedMutation;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class SubscribeMutation extends ParentAuthedMutation
{
    protected $attributes = [
        'name' => 'subscribe',
        'description' => 'Activate a subscription plan for the current parent.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'subscription_id' => ['type' => Type::nonNull(Type::string())],
            'auto_renew' => ['type' => Type::boolean(), 'defaultValue' => false],
        ];
    }

    protected function emptyPayload(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            /** @var Subscription|null $subscription */
            $subscription = Subscription::query()
                ->whereKey((string) $args['subscription_id'])
                ->where('is_available', true)
                ->first();

            if (! $subscription instanceof Subscription) {
                throw ValidationException::withMessages([
                    'subscription_id' => __('validation.custom.subscription.not_found_or_unavailable'),
                ]);
            }

            app(SubscriptionService::class)->subscribe(
                $user,
                $subscription,
                (bool) ($args['auto_renew'] ?? false),
            );

            return [];
        });
    }
}
