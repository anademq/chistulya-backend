<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class AchievementRequirementsType extends GraphQLType
{
    protected $attributes = [
        'name' => 'AchievementRequirements',
        'description' => 'Unlock requirements that a child must satisfy to complete an achievement.',
    ];

    public function fields(): array
    {
        return [
            'subscription' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Whether an active subscription is required to unlock this achievement.',
            ],
            'daily_tasks' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                'description' => 'List of daily task UUIDs that must be completed to satisfy this requirement.',
            ],
            'challenges' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                'description' => 'List of challenge UUIDs that must be completed to satisfy this requirement.',
            ],
        ];
    }
}
