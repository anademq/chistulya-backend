<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DailyTaskStatus;
use App\Models\Child\ChildDailyTask;
use App\Models\DailyTask;
use App\Models\DailyTaskCategory;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DailyTaskService
{
    public const CATEGORIES_CACHE_KEY = 'categories:daily_tasks';
    public function listAvailable(User $child, int $page, int $perPage = 20): LengthAwarePaginator
    {
        return DailyTask::availableFor($child)
            ->with(['category', 'media'])
            ->orderBy('title')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function listSelected(User $child, int $page, int $perPage = 10): LengthAwarePaginator
    {
        $this->resetStaleForChild($child);

        return ChildDailyTask::query()
            ->with('dailyTask')
            ->where('child_id', $child->id)
            ->orderByDesc('updated_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Delete completed/claimed daily tasks that belong to a previous local day for this child.
     * Ensures stale tasks are gone even if the cron hasn't fired yet for the child's timezone.
     */
    private function resetStaleForChild(User $child): void
    {
        $tz = (string) (DB::table('profiles')
            ->where('user_id', $child->id)
            ->value('timezone') ?? 'UTC');

        $localMidnightUtc = now()->setTimezone($tz)->startOfDay()->utc()->toDateTimeString();

        ChildDailyTask::query()
            ->where('child_id', $child->id)
            ->whereIn('status', [DailyTaskStatus::COMPLETED, DailyTaskStatus::REWARD_CLAIMED])
            ->where('updated_at', '<', $localMidnightUtc)
            ->delete();
    }

    public function select(User $child, string $dailyTaskId): ChildDailyTask
    {
        $dailyTask = DailyTask::whereKey($dailyTaskId)->firstOrFail();

        if (! $dailyTask->isGlobal() && ! $dailyTask->isAssignedToChild($child)) {
            throw ValidationException::withMessages([
                'daily_task_id' => __('validation.exists', ['attribute' => 'daily_task_id']),
            ]);
        }

        $childDailyTask = ChildDailyTask::firstOrCreate(
            ['daily_task_id' => $dailyTask->id, 'child_id' => $child->id],
            ['status' => DailyTaskStatus::SELECTED],
        );

        if ($childDailyTask->wasRecentlyCreated) {
            app(AnalyticsService::class)->incrementDailyTaskSelected(
                $child,
                (int) $dailyTask->category_id,
                now(),
            );
        }

        return $childDailyTask;
    }

    public function unselect(User $child, string $dailyTaskId): void
    {
        ChildDailyTask::where('daily_task_id', $dailyTaskId)
            ->where('child_id', $child->id)
            ->delete();
    }

    public function complete(User $child, string $dailyTaskId): ChildDailyTask
    {
        $childDailyTask = ChildDailyTask::where('daily_task_id', $dailyTaskId)
            ->where('child_id', $child->id)
            ->with('dailyTask.category')
            ->firstOrFail();

        if ($childDailyTask->isCompleted()) {
            throw ValidationException::withMessages([
                'daily_task_id' => __('validation.in', ['attribute' => 'daily_task_id']),
            ]);
        }

        $completedAt = now();

        $childDailyTask->forceFill([
            'status' => DailyTaskStatus::COMPLETED,
            'completed_at' => $completedAt,
        ])->save();

        app(AnalyticsService::class)->incrementDailyTaskCompleted(
            $child,
            (int) $childDailyTask->dailyTask->category_id,
            $completedAt,
        );

        return $childDailyTask;
    }

    /**
     * @return array{child_daily_task:ChildDailyTask,reward:array{level:int,xp:int,coins:int,granted_xp:int,granted_coins:int}}
     */
    public function claim(User $child, string $dailyTaskId): array
    {
        return DB::transaction(function () use ($child, $dailyTaskId): array {
            $childDailyTask = ChildDailyTask::lockForUpdate()
                ->where('daily_task_id', $dailyTaskId)
                ->where('child_id', $child->id)
                ->with('dailyTask')
                ->firstOrFail();

            if (! $childDailyTask->canClaimReward()) {
                throw ValidationException::withMessages([
                    'daily_task_id' => __('validation.in', ['attribute' => 'daily_task_id']),
                ]);
            }

            $dailyTask = $childDailyTask->dailyTask;
            $reward = app(RewardService::class)->grant(
                $child,
                (int) $dailyTask->reward_xp,
                (int) $dailyTask->reward_coins,
            );

            $childDailyTask->forceFill([
                'status' => DailyTaskStatus::REWARD_CLAIMED,
                'reward_claimed_at' => now(),
            ])->save();

            return [
                'child_daily_task' => $childDailyTask,
                'reward' => $reward,
            ];
        });
    }

    // Category management (admin only)

    public function createCategory(array $data): DailyTaskCategory
    {
        $category = DailyTaskCategory::create([
            'slug' => $data['slug'],
            'title' => $data['title'],
            'order_column' => $data['order_column'] ?? null,
        ]);

        Cache::forget(self::CATEGORIES_CACHE_KEY);

        return $category;
    }

    public function updateCategory(DailyTaskCategory $category, array $data): DailyTaskCategory
    {
        $category->update(array_filter([
            'slug' => $data['slug'] ?? null,
            'title' => $data['title'] ?? null,
            'order_column' => $data['order_column'] ?? null,
        ], fn ($v) => $v !== null));

        Cache::forget(self::CATEGORIES_CACHE_KEY);

        return $category->fresh();
    }

    public function deleteCategory(DailyTaskCategory $category): void
    {
        $category->delete();

        Cache::forget(self::CATEGORIES_CACHE_KEY);
    }
}
