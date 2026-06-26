<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AchievementStatus;
use App\Enums\ChallengeStatus;
use App\Enums\DailyTaskStatus;
use App\Models\Achievement;
use App\Models\Child\ChildAchievement;
use App\Models\Child\ChildChallenge;
use App\Models\Child\ChildDailyTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AchievementService
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    /**
     * @return Collection<int, Achievement>
     */
    public function getAvailableAchievements(User $child): Collection
    {
        return Achievement::query()->available()->get();
    }

    /**
     * Evaluate all achievements and upsert child records accordingly.
     *
     * @return Collection<int, Achievement>
     */
    public function syncAndList(User $child): Collection
    {
        /** @var Collection<int, Achievement> $achievements */
        $achievements = Achievement::query()->orderBy('created_at')->get();

        $existingMap = ChildAchievement::where('child_id', $child->id)
            ->get()
            ->keyBy('achievement_id');

        // Pre-fetch subscription status once to avoid N queries in the loop
        $hasSubscription = $this->subscriptionService->childHasInheritedSubscription($child);

        // Collect all required IDs across every achievement for a single bulk fetch
        $allTaskIds = [];
        $allChallengeIds = [];
        foreach ($achievements as $achievement) {
            array_push($allTaskIds, ...$achievement->requirements->dailyTasks()->all());
            array_push($allChallengeIds, ...$achievement->requirements->challenges()->all());
        }

        $completedTaskIds = [];
        if ($allTaskIds !== []) {
            $completedTaskIds = ChildDailyTask::where('child_id', $child->id)
                ->whereIn('daily_task_id', array_unique($allTaskIds))
                ->whereIn('status', [DailyTaskStatus::COMPLETED, DailyTaskStatus::REWARD_CLAIMED])
                ->pluck('daily_task_id')
                ->flip()
                ->all();
        }

        $completedChallengeIds = [];
        if ($allChallengeIds !== []) {
            $completedChallengeIds = ChildChallenge::where('child_id', $child->id)
                ->whereIn('challenge_id', array_unique($allChallengeIds))
                ->whereIn('status', [ChallengeStatus::COMPLETED, ChallengeStatus::REWARD_CLAIMED])
                ->pluck('challenge_id')
                ->flip()
                ->all();
        }

        foreach ($achievements as $achievement) {
            $existing = $existingMap->get($achievement->id);

            if ($existing?->isRewardClaimed()) {
                continue;
            }

            $canComplete = $this->isCompletedByChild(
                $achievement,
                $hasSubscription,
                $completedTaskIds,
                $completedChallengeIds,
            );
            $completedAt = $canComplete ? ($existing?->completed_at ?? now()) : null;

            ChildAchievement::updateOrCreate(
                ['achievement_id' => $achievement->id, 'child_id' => $child->id],
                [
                    'status' => $canComplete ? AchievementStatus::COMPLETED : AchievementStatus::IN_PROGRESS,
                    'completed_at' => $completedAt,
                ],
            );
        }

        return $achievements->load(['childAchievements' => fn ($q) => $q->where('child_id', $child->id)]);
    }

    /**
     * @return array{child_achievement:ChildAchievement,reward:array{level:int,xp:int,coins:int,granted_xp:int,granted_coins:int}}
     */
    public function claim(User $child, string $achievementId): array
    {
        return DB::transaction(function () use ($child, $achievementId): array {
            $childAchievement = ChildAchievement::lockForUpdate()
                ->where('achievement_id', $achievementId)
                ->where('child_id', $child->id)
                ->with('achievement')
                ->firstOrFail();

            if (! $childAchievement->canClaimReward()) {
                throw ValidationException::withMessages([
                    'achievement_id' => __('validation.in', ['attribute' => 'achievement_id']),
                ]);
            }

            $achievement = $childAchievement->achievement;
            $reward = app(RewardService::class)->grant(
                $child,
                (int) $achievement->reward_xp,
                (int) $achievement->reward_coins,
            );

            $childAchievement->forceFill([
                'status' => AchievementStatus::REWARD_CLAIMED,
                'reward_claimed_at' => now(),
            ])->save();

            return [
                'child_achievement' => $childAchievement,
                'reward' => $reward,
            ];
        });
    }

    /**
     * @param  array<string, int>  $completedTaskIds   task_id => position (flip of pluck)
     * @param  array<string, int>  $completedChallengeIds  challenge_id => position (flip of pluck)
     */
    private function isCompletedByChild(
        Achievement $achievement,
        bool $hasSubscription,
        array $completedTaskIds,
        array $completedChallengeIds,
    ): bool {
        $requirements = $achievement->requirements;

        if ($requirements->isSubscriptionRequired() && ! $hasSubscription) {
            return false;
        }

        foreach ($requirements->dailyTasks()->all() as $taskId) {
            if (! array_key_exists($taskId, $completedTaskIds)) {
                return false;
            }
        }

        foreach ($requirements->challenges()->all() as $challengeId) {
            if (! array_key_exists($challengeId, $completedChallengeIds)) {
                return false;
            }
        }

        return true;
    }
}
