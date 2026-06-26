<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Parent;

use App\Enums\DailyTaskScope;
use App\GraphQL\Mutations\ParentAuthedMutation;
use App\Models\DailyTask;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class DeleteCustomDailyTaskForChildMutation extends ParentAuthedMutation
{
    protected $attributes = [
        'name' => 'deleteCustomDailyTaskForChild',
        'description' => 'Parent: soft-delete a custom daily task that the parent previously created for a child.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'task_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the custom daily task to delete.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'task_id' => ['required', 'uuid', 'exists:daily_tasks,id'],
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
            DailyTask::where('id', $args['task_id'])
                ->where('created_by', $user->id)
                ->where('scope', DailyTaskScope::ASSIGNED)
                ->firstOrFail()
                ->delete();

            return [];
        });
    }
}
