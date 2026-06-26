<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Child;

use App\GraphQL\Mutations\ChildAuthedMutation;
use App\Models\User;
use App\Services\DailyTaskService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ClaimDailyTaskRewardMutation extends ChildAuthedMutation
{
    protected $attributes = [
        'name' => 'claimDailyTaskReward',
        'description' => 'Claim the reward for a completed daily task.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ClaimDailyTaskRewardPayload');
    }

    public function args(): array
    {
        return [
            'daily_task_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the completed daily task whose reward you want to claim.',
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
        return ['child_daily_task' => null, 'reward' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            return app(DailyTaskService::class)->claim($user, $args['daily_task_id']);
        });
    }
}
