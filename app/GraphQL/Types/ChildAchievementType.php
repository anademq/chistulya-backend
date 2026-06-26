<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Child\ChildAchievement;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ChildAchievementType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ChildAchievement',
        'description' => 'A child\'s achievement record tracking unlock progress and reward status.',
        'model' => ChildAchievement::class,
    ];

    public function fields(): array
    {
        return [
            'achievement_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the achievement definition.',
            ],
            'child_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the child who owns this record.',
            ],
            'status' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Current status (e.g. "in_progress", "completed", "reward_claimed").',
                'resolve' => static fn(ChildAchievement $achievement): string => $achievement->status->value,
            ],
            'completed_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the achievement was unlocked. Null if not yet completed.',
            ],
            'reward_claimed_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the reward was claimed. Null if not yet claimed.',
            ],
            'achievement' => [
                'type' => GraphQL::type('Achievement'),
                'description' => 'The associated achievement definition.',
            ],
        ];
    }
}
