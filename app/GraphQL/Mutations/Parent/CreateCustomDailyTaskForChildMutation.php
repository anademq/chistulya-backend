<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Parent;

use App\Enums\DailyTaskScope;
use App\GraphQL\Mutations\ParentAuthedMutation;
use App\Models\Child\Assignment\DailyTaskAssignment;
use App\Models\DailyTask;
use App\Models\User;
use App\Services\FamilyService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class CreateCustomDailyTaskForChildMutation extends ParentAuthedMutation
{
    protected $attributes = [
        'name' => 'createCustomDailyTaskForChild',
        'description' => 'Parent: create a custom daily task and assign it to a specific child.',
    ];

    public function type(): Type
    {
        return GraphQL::type('DailyTaskPayload');
    }

    public function args(): array
    {
        return [
            'child_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the child to assign the task to.',
            ],
            'category_id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID of the task category.',
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Display title (max 150 characters).',
            ],
            'short_description' => [
                'type' => Type::string(),
                'description' => 'Brief one-line description (max 250 characters).',
            ],
            'description' => [
                'type' => Type::string(),
                'description' => 'Full detailed description.',
            ],
            'reward_xp' => [
                'type' => Type::int(),
                'defaultValue' => 0,
                'description' => 'XP reward. Defaults to 0.',
            ],
            'reward_coins' => [
                'type' => Type::int(),
                'defaultValue' => 0,
                'description' => 'Coin reward. Defaults to 0.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'child_id' => ['required', 'uuid', 'exists:users,id'],
            'category_id' => ['required', 'integer', 'exists:daily_task_categories,id'],
            'title' => ['required', 'string', 'max:150'],
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
        app(FamilyService::class)->assertParentAccessToChild($user, $args['child_id']);

        return $this->wrapPayload(function () use ($user, $args): array {
            $dailyTask = DailyTask::create([
                'created_by' => $user->id,
                'scope' => DailyTaskScope::ASSIGNED,
                'category_id' => (int) $args['category_id'],
                'title' => $args['title'],
                'short_description' => $args['short_description'] ?? null,
                'description' => $args['description'] ?? null,
                'reward_xp' => (int) ($args['reward_xp'] ?? 0),
                'reward_coins' => (int) ($args['reward_coins'] ?? 0),
            ]);

            DailyTaskAssignment::firstOrCreate([
                'daily_task_id' => $dailyTask->id,
                'child_id' => $args['child_id'],
            ]);

            return ['daily_task' => $dailyTask->load('category')];
        });
    }
}
