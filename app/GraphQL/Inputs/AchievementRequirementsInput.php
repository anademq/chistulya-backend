<?php

namespace App\GraphQL\Inputs;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;

class AchievementRequirementsInput extends InputType
{
    protected $attributes = [
        'name' => 'AchievementRequirementsInput',
        'description' => 'Input object for Achievement Requirements.',
    ];

    public function fields(): array
    {
        return [
            'subscription' => [
                'type' => Type::boolean(),
                'defaultValue' => false,
                'description' => 'Subscription details.',
            ],
            'daily_tasks' => [
                'type' => Type::listOf(Type::nonNull(Type::string())),
                'description' => 'Daily tasks field.',
            ],
            'challenges' => [
                'type' => Type::listOf(Type::nonNull(Type::string())),
                'description' => 'Challenges field.',
            ],
        ];
    }
}
