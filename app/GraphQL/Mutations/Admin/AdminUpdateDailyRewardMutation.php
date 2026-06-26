<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\DailyReward;
use App\Services\DailyRewardService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminUpdateDailyRewardMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'updateDailyReward',
        'description' => 'Admin: update an existing daily login reward. Day 0 is the fixed post-streak reward given every day after the full 7-day cycle is completed; days 1–7 are sequential streak rewards.',
    ];

    public function type(): Type
    {
        return GraphQL::type('DailyRewardPayload');
    }

    public function args(): array
    {
        return [
            'day' => ['type' => Type::nonNull(Type::int()), 'description' => 'Day number to update (1–7).'],
            'reward_xp' => ['type' => Type::int()],
            'reward_coins' => ['type' => Type::int()],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'day' => ['required', 'integer', 'min:0', 'max:30', 'exists:daily_rewards,day'],
            'reward_xp' => ['nullable', 'integer', 'min:0'],
            'reward_coins' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['daily_reward' => null];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $reward = DailyReward::where('day', (int) $args['day'])->firstOrFail();

            $fields = array_filter(
                array_intersect_key($args, array_flip(['reward_xp', 'reward_coins'])),
                static fn ($v) => $v !== null,
            );

            if (! empty($fields)) {
                $reward->forceFill($fields)->save();
                DailyRewardService::flushCache();
            }

            return ['daily_reward' => $reward->refresh()];
        });
    }
}
