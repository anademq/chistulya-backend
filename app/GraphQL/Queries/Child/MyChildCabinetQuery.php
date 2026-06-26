<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Child;

use App\Models\User;
use App\GraphQL\Support\SelectionFieldSet;
use App\Services\SubscriptionService;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use App\GraphQL\Queries\ChildAuthedQuery;

class MyChildCabinetQuery extends ChildAuthedQuery
{
    protected $attributes = [
        'name' => 'childDashboard',
        'description' => 'Returns the dashboard summary for the authenticated child: wallet balance, XP and level, consecutive login streak count, and subscription status.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ChildCabinet');
    }

    public function resolve($root, array $args, $context, ResolveInfo $info): array
    {
        /** @var User $user */
        $user = auth()->user();
        $selection = SelectionFieldSet::fromInfo($info, 2);
        $wallet = SelectionFieldSet::has($selection, 'wallet')
            ? $user->wallet()->firstOrCreate([], ['coins' => 0])
            : null;
        $exp = SelectionFieldSet::has($selection, 'exp')
            ? $user->exp()->firstOrCreate([], ['level' => 1, 'xp' => 0])
            : null;
        $progress = SelectionFieldSet::has($selection, 'current_daily_reward_day')
            ? $user->dailyReward()->firstOrCreate([], ['current_day' => 1])
            : null;
        $hasActiveSubscription = SelectionFieldSet::has($selection, 'has_active_subscription')
            ? app(SubscriptionService::class)->effectiveActiveSubscription($user) !== null
            : false;

        return [
            'wallet' => $wallet,
            'exp' => $exp,
            'current_daily_reward_day' => (int) ($progress?->current_day ?? 1),
            'has_active_subscription' => $hasActiveSubscription,
        ];
    }
}

