<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Child\ChildDailyTask;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ChildDailyTaskType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ChildDailyTask',
        'description' => 'A child\'s progress record for a selected daily task.',
        'model' => ChildDailyTask::class,
    ];

    public function fields(): array
    {
        return [
            'daily_task_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the daily task definition.',
            ],
            'child_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the child who selected the task.',
            ],
            'status' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Current status of the task (e.g. "selected", "completed", "reward_claimed").',
                'resolve' => static fn(ChildDailyTask $task): string => $task->status->value,
            ],
            'completed_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the task was marked as completed. Null if not yet completed.',
            ],
            'reward_claimed_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the reward was claimed. Null if not yet claimed.',
            ],
            'daily_task' => [
                'type' => GraphQL::type('DailyTask'),
                'description' => 'The associated daily task definition.',
            ],
        ];
    }
}
