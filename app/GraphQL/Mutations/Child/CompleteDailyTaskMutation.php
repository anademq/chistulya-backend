<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Child;

use App\GraphQL\Mutations\ChildAuthedMutation;
use App\Models\User;
use App\Services\DailyTaskService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class CompleteDailyTaskMutation extends ChildAuthedMutation
{
    protected $attributes = [
        'name' => 'completeDailyTask',
        'description' => 'Mark a selected daily task as completed.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ChildDailyTaskPayload');
    }

    public function args(): array
    {
        return [
            'daily_task_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the daily task to mark as completed.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'daily_task_id' => ['required', 'uuid', 'exists:daily_tasks,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['child_daily_task' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            return ['child_daily_task' => app(DailyTaskService::class)->complete($user, $args['daily_task_id'])];
        });
    }
}
