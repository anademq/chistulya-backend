<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\InputType;

class AchievementRequirementsInput extends InputType
{
    protected $attributes = [
        'name' => 'AchievementRequirementsInput',
        'description' => 'Input for defining achievement unlock requirements.',
    ];

    public function fields(): array
    {
        return [
            'subscription' => [
                'type' => Type::boolean(),
                'defaultValue' => false,
                'description' => 'Whether an active subscription is required.',
            ],
            'daily_tasks' => [
                'type' => Type::listOf(Type::nonNull(Type::string())),
                'description' => 'List of daily task UUIDs that must be completed.',
            ],
            'challenges' => [
                'type' => Type::listOf(Type::nonNull(Type::string())),
                'description' => 'List of challenge UUIDs that must be completed.',
            ],
        ];
    }
}
