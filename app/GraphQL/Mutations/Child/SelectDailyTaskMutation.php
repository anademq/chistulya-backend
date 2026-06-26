<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Child;

use App\GraphQL\Mutations\ChildAuthedMutation;
use App\Models\User;
use App\Services\DailyTaskService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class SelectDailyTaskMutation extends ChildAuthedMutation
{
    protected $attributes = [
        'name' => 'selectDailyTask',
        'description' => 'Select a daily task to work on today.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ChildDailyTaskPayload');
    }

    public function args(): array
    {
        return [
            'daily_task_id' => ['type' => Type::nonNull(Type::string())],
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
            return ['child_daily_task' => app(DailyTaskService::class)->select($user, $args['daily_task_id'])];
        });
    }
}
