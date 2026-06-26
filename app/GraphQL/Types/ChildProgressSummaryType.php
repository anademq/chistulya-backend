<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ChildProgressSummaryType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ChildProgressSummary',
        'description' => 'Aggregated progress summary for a child: daily task counts and challenge status breakdown.',
    ];

    public function fields(): array
    {
        return [
            'tasks_total' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Total number of daily tasks the child currently has selected (any status).',
            ],
            'tasks_completed' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Number of daily tasks completed (status COMPLETED or REWARD_CLAIMED).',
            ],
            'challenges_active' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Number of challenges currently in progress.',
            ],
            'challenges_completed' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Number of challenges completed (status COMPLETED or REWARD_CLAIMED).',
            ],
        ];
    }
}
