<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Parent;

use App\Enums\ChallengeStatus;
use App\Enums\DailyTaskStatus;
use App\GraphQL\Queries\ParentAuthedQuery;
use App\Models\Child\ChildChallenge;
use App\Models\Child\ChildDailyTask;
use App\Models\User;
use App\Services\FamilyService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ChildProgressSummaryQuery extends ParentAuthedQuery
{
    protected $attributes = [
        'name' => 'childProgressSummary',
        'description' => 'Returns an aggregated progress summary for a linked child: daily task counts and challenge status breakdown.',
    ];

    public function type(): Type
    {
        return Type::nonNull(GraphQL::type('ChildProgressSummary'));
    }

    public function args(): array
    {
        return [
            'child_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the child to retrieve progress for.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'child_id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();
        app(FamilyService::class)->assertParentAccessToChild($user, $args['child_id']);

        $child = User::whereKey($args['child_id'])->firstOrFail();

        $taskCounts = ChildDailyTask::where('child_id', $child->id)
            ->selectRaw(
                'COUNT(*) as total, SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as completed',
                [DailyTaskStatus::COMPLETED->value, DailyTaskStatus::REWARD_CLAIMED->value],
            )
            ->first();

        $challengeCounts = ChildChallenge::where('child_id', $child->id)
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active, SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as completed',
                [ChallengeStatus::IN_PROGRESS->value, ChallengeStatus::COMPLETED->value, ChallengeStatus::REWARD_CLAIMED->value],
            )
            ->first();

        return [
            'tasks_total' => (int) ($taskCounts?->total ?? 0),
            'tasks_completed' => (int) ($taskCounts?->completed ?? 0),
            'challenges_active' => (int) ($challengeCounts?->active ?? 0),
            'challenges_completed' => (int) ($challengeCounts?->completed ?? 0),
        ];
    }
}
