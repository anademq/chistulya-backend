<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Child;

use App\GraphQL\Mutations\ChildAuthedMutation;
use App\Models\User;
use App\Services\DailyRewardService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ClaimDailyRewardMutation extends ChildAuthedMutation
{
    protected $attributes = [
        'name' => 'claimDailyReward',
        'description' => 'Claim the daily login reward for today. Increments the consecutive login streak counter each day. If the streak is broken (a day is missed), the counter resets to 1. Once all configured streak days are completed, a fixed post-streak reward is given every subsequent day.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ClaimDailyRewardPayload');
    }

    protected function emptyPayload(): array
    {
        return ['day' => null, 'reward' => null, 'grant' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();
        return $this->wrapPayload(function () use ($user): array {
            return app(DailyRewardService::class)->claim($user);
        });
    }
}
