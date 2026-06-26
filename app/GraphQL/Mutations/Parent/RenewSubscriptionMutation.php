<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Parent;

use App\GraphQL\Mutations\ParentAuthedMutation;
use App\Models\Subscription;
use App\Models\User;
use App\Models\User\UserSubscription;
use App\Services\SubscriptionService;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class RenewSubscriptionMutation extends ParentAuthedMutation
{
    protected $attributes = [
        'name' => 'renewSubscription',
        'description' => 'Renew the active subscription for the current parent.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'subscription_id' => ['type' => Type::string()],
            'auto_renew' => ['type' => Type::boolean()],
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
            /** @var UserSubscription|null $activeSubscription */
            $activeSubscription = $user->userSubscription()
                ->where('expires_at', '>', now())
                ->with('subscription')
                ->first();

            if (! $activeSubscription instanceof UserSubscription) {
                throw ValidationException::withMessages([
                    'subscription' => 'Active subscription was not found.',
                ]);
            }

            $targetPlan = $activeSubscription->subscription;

            if (! empty($args['subscription_id'])) {
                /** @var Subscription $targetPlan */
                $targetPlan = Subscription::query()
                    ->whereKey((string) $args['subscription_id'])
                    ->where('is_available', true)
                    ->firstOrFail();
            }

            $renewed = app(SubscriptionService::class)->renew($user, $targetPlan);

            if (isset($args['auto_renew'])) {
                $renewed->update(['auto_renew' => (bool) $args['auto_renew']]);
            }

            return [];
        });
    }
}
