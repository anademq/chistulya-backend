<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use App\Models\User\UserLink;
use App\Models\User\UserSubscription;

class SubscriptionService
{
    public const AVAILABLE_CACHE_KEY = 'graphql:subscriptions:available';
    public function getActiveSubscription(User $user): ?UserSubscription
    {
        /** @var UserSubscription|null $subscription */
        $subscription = UserSubscription::query()
            ->where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->with('subscription')
            ->first();

        return $subscription;
    }

    public function hasActiveSubscription(User $user): bool
    {
        return UserSubscription::query()
            ->where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function childHasInheritedSubscription(User $child): bool
    {
        return UserSubscription::query()
            ->whereIn('user_id', function ($query) use ($child): void {
                $query->select('parent_id')
                    ->from((new UserLink())->getTable())
                    ->where('child_id', $child->id);
            })
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function activeInheritedSubscriptionForChild(User $child): ?UserSubscription
    {
        /** @var UserSubscription|null $subscription */
        $subscription = UserSubscription::query()
            ->whereIn('user_id', function ($query) use ($child): void {
                $query->select('parent_id')
                    ->from((new UserLink())->getTable())
                    ->where('child_id', $child->id);
            })
            ->where('expires_at', '>', now())
            ->with('subscription')
            ->orderByDesc('expires_at')
            ->first();

        return $subscription;
    }

    public function effectiveActiveSubscription(User $user): ?UserSubscription
    {
        $direct = $this->getActiveSubscription($user);
        if ($direct instanceof UserSubscription) {
            return $direct;
        }

        return $this->activeInheritedSubscriptionForChild($user);
    }

    /**
     * Subscribe a user to a plan for the first time (or replace an expired subscription).
     */
    public function subscribe(User $user, Subscription $subscription, bool $autoRenew = false): UserSubscription
    {
        return UserSubscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'subscription_id' => $subscription->id,
                'auto_renew' => $autoRenew,
                'started_at' => now(),
                'expires_at' => now()->addDays((int) $subscription->duration_days),
            ],
        );
    }

    /**
     * Extend an existing subscription (or activate a new one if none exists).
     * If the current subscription is still active, extends from its expiry date.
     */
    public function renew(User $user, Subscription $subscription): UserSubscription
    {
        $existing = UserSubscription::where('user_id', $user->id)->first();

        $baseDate = $existing?->expires_at?->isFuture() ? $existing->expires_at : now();

        return UserSubscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'subscription_id' => $subscription->id,
                'started_at' => $existing?->started_at ?? now(),
                'expires_at' => $baseDate->copy()->addDays((int) $subscription->duration_days),
            ],
        );
    }
}
