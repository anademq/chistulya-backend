<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\DailyTask;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminDeleteDailyTaskMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'deleteDailyTask',
        'description' => 'Admin: soft-delete a daily task.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the daily task to delete.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:daily_tasks,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            DailyTask::whereKey($args['id'])->firstOrFail()->delete();

            return [];
        });
    }
}
