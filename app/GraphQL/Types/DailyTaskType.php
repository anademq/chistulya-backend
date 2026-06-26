<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Enums\DailyTaskScope;
use App\Models\DailyTask;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class DailyTaskType extends GraphQLType
{
    protected $attributes = [
        'name' => 'DailyTask',
        'description' => 'A daily task definition that children can select and complete for rewards.',
        'model' => DailyTask::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Unique UUID identifier of the daily task.',
            ],
            'scope' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Raw scope value. Admin use only — clients should use the `source` field instead.',
                'resolve' => static fn(DailyTask $task): string => $task->scope->value,
            ],
            'source' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'How this task was made available: "global" (system task for all children) or "custom" (created by a parent or admin for specific children).',
                'resolve' => static fn(DailyTask $task): string => match ($task->scope) {
                    DailyTaskScope::GLOBAL => 'global',
                    default => 'custom',
                },
            ],
            'creator_id' => [
                'type' => Type::string(),
                'description' => 'UUID of the parent who created this task. Null for system (global) tasks and admin-created tasks.',
                'resolve' => static function (DailyTask $task): ?string {
                    if ($task->scope === DailyTaskScope::GLOBAL || $task->created_by === null) {
                        return null;
                    }

                    $task->loadMissing('creator');

                    if ($task->creator?->isAdminUser()) {
                        return null;
                    }

                    return $task->created_by;
                },
            ],
            'category_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'ID of the task category.',
            ],
            'category' => [
                'type' => GraphQL::type('DailyTaskCategory'),
                'description' => 'Task category details.',
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Short display title of the task.',
            ],
            'short_description' => [
                'type' => Type::string(),
                'description' => 'Brief one-line description. Null if not set.',
            ],
            'description' => [
                'type' => Type::string(),
                'description' => 'Full detailed description. Null if not set.',
            ],
            'media' => [
                'type' => Type::listOf(GraphQL::type('Media')),
                'description' => 'Uploaded media for this task.',
                'resolve' => static fn(DailyTask $task): Collection => $task->media,
            ],
            'reward_xp' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'XP reward granted upon completion.',
            ],
            'reward_coins' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Coin reward granted upon completion.',
            ],
            'created_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the task was created.',
            ],
        ];
    }
}
