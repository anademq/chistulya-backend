<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Parent;

use App\Enums\DailyTaskScope;
use App\GraphQL\Mutations\ParentAuthedMutation;
use App\Models\DailyTask;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class UpdateCustomDailyTaskForChildMutation extends ParentAuthedMutation
{
    protected $attributes = [
        'name' => 'updateCustomDailyTaskForChild',
        'description' => 'Parent: update a custom daily task that the parent previously created for a child.',
    ];

    public function type(): Type
    {
        return GraphQL::type('DailyTaskPayload');
    }

    public function args(): array
    {
        return [
            'task_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the custom daily task to update.',
            ],
            'title' => [
                'type' => Type::string(),
                'description' => 'New display title (max 150 characters).',
            ],
            'short_description' => [
                'type' => Type::string(),
                'description' => 'New brief one-line description (max 250 characters).',
            ],
            'description' => [
                'type' => Type::string(),
                'description' => 'New full detailed description.',
            ],
            'reward_xp' => [
                'type' => Type::int(),
                'description' => 'New XP reward.',
            ],
            'reward_coins' => [
                'type' => Type::int(),
                'description' => 'New coin reward.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'task_id' => ['required', 'uuid', 'exists:daily_tasks,id'],
            'title' => ['nullable', 'string', 'max:150'],
            'short_description' => ['nullable', 'string', 'max:250'],
            'description' => ['nullable', 'string'],
            'reward_xp' => ['nullable', 'integer', 'min:0'],
            'reward_coins' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['daily_task' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            $task = DailyTask::where('id', $args['task_id'])
                ->where('created_by', $user->id)
                ->where('scope', DailyTaskScope::ASSIGNED)
                ->firstOrFail();

            $task->update(array_filter([
                'title' => $args['title'] ?? null,
                'short_description' => $args['short_description'] ?? null,
                'description' => $args['description'] ?? null,
                'reward_xp' => isset($args['reward_xp']) ? (int) $args['reward_xp'] : null,
                'reward_coins' => isset($args['reward_coins']) ? (int) $args['reward_coins'] : null,
            ], fn ($v) => $v !== null));

            return ['daily_task' => $task->fresh()->load('category')];
        });
    }
}
