<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class RewardGrantType extends GraphQLType
{
    protected $attributes = [
        'name' => 'RewardGrant',
        'description' => 'Result of awarding XP and coins to a child, including the resulting level and balance.',
    ];

    public function fields(): array
    {
        return [
            'level' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Child\'s level after the reward was applied.',
            ],
            'xp' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Child\'s total XP after the reward was applied.',
            ],
            'coins' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Child\'s coin balance after the reward was applied.',
            ],
            'granted_xp' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Amount of XP granted by this reward.',
            ],
            'granted_coins' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Amount of coins granted by this reward.',
            ],
        ];
    }
}
