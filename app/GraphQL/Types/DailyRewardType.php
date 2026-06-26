<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\DailyReward;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class DailyRewardType extends GraphQLType
{
    protected $attributes = [
        'name' => 'DailyReward',
        'description' => 'Configured reward tier for a streak day. Days 1–N define the sequential rewards; day 0 is the fixed post-streak reward given every day once the full streak is completed.',
        'model' => DailyReward::class,
    ];

    public function fields(): array
    {
        return [
            'day' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Streak tier identifier: 1–N for specific streak days, 0 for the fixed post-streak reward (awarded after all configured days are completed).',
            ],
            'reward_xp' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'XP granted on this day.',
            ],
            'reward_coins' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Coins granted on this day.',
            ],
        ];
    }
}
