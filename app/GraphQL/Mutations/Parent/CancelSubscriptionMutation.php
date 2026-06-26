<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Parent;

use App\GraphQL\Mutations\ParentAuthedMutation;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class CancelSubscriptionMutation extends ParentAuthedMutation
{
    protected $attributes = [
        'name' => 'cancelSubscription',
        'description' => 'Cancel the active subscription for the current parent.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    protected function emptyPayload(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user): array {
            /** @var \App\Models\User\UserSubscription|null $activeSubscription */
            $activeSubscription = $user->userSubscription()
                ->where('expires_at', '>', now())
                ->first();

            if (! $activeSubscription) {
                throw ValidationException::withMessages([
                    'subscription' => __('validation.custom.subscription.not_found'),
                ]);
            }

            $activeSubscription->update([
                'auto_renew' => false,
                'expires_at' => now(),
            ]);

            return [];
        });
    }
}
