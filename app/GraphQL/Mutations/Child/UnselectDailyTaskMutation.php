<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Child;

use App\GraphQL\Mutations\ChildAuthedMutation;
use App\Models\User;
use App\Services\DailyTaskService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class UnselectDailyTaskMutation extends ChildAuthedMutation
{
    protected $attributes = [
        'name' => 'unselectDailyTask',
        'description' => 'Remove a daily task from today\'s selection.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'daily_task_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the daily task to deselect.',
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
        return [];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            app(DailyTaskService::class)->unselect($user, $args['daily_task_id']);

            return [];
        });
    }
}
