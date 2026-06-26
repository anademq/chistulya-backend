<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\DailyReward;
use App\Services\DailyRewardService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminCreateDailyRewardMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'createDailyReward',
        'description' => 'Admin: create a daily login reward. Use day 1–7 for streak rewards and day 0 for the fixed post-streak reward given every day after the full 7-day cycle is completed.',
    ];

    public function type(): Type
    {
        return GraphQL::type('DailyRewardPayload');
    }

    public function args(): array
    {
        return [
            'day' => ['type' => Type::nonNull(Type::int())],
            'reward_xp' => ['type' => Type::int(), 'defaultValue' => 0],
            'reward_coins' => ['type' => Type::int(), 'defaultValue' => 0],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'day' => ['required', 'integer', 'min:0', 'max:30', 'unique:daily_rewards,day'],
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
            $reward = DailyReward::create([
                'day' => (int) $args['day'],
                'reward_xp' => (int) ($args['reward_xp'] ?? 0),
                'reward_coins' => (int) ($args['reward_coins'] ?? 0),
            ]);

            DailyRewardService::flushCache();

            return ['daily_reward' => $reward];
        });
    }
}
