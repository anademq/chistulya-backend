<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ChallengeStatus;
use App\Enums\DailyTaskStatus;
use App\Models\Child\ChildChallenge;
use App\Models\Child\ChildDailyTask;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    /**
     * Delete completed daily tasks for children whose local date has advanced past the task's
     * completion day. Uses a subquery JOIN — one query per distinct child timezone.
     */
    public function resetDailyTasksAtMidnight(?CarbonInterface $now = null): int
    {
        $now ??= now();
        $total = 0;

        foreach ($this->distinctChildTimezones() as $tz) {
            $localMidnightUtc = $now->clone()->setTimezone($tz)->startOfDay()->utc()->toDateTimeString();

            $total += ChildDailyTask::query()
                ->whereIn('child_id', $this->childIdsByTimezone($tz))
                ->whereIn('status', [DailyTaskStatus::COMPLETED->value, DailyTaskStatus::REWARD_CLAIMED->value])
                ->where('updated_at', '<', $localMidnightUtc)
                ->delete();
        }

        return $total;
    }

    /**
     * Mark IN_PROGRESS challenges as FAILED when the child skipped a day (in their local timezone).
     * One UPDATE query per distinct timezone.
     */
    public function failSkippedChallenges(?CarbonInterface $now = null): int
    {
        $now ??= now();
        $total = 0;

        foreach ($this->distinctChildTimezones() as $tz) {
            // "Skipped" = last_progress_at is before the start of yesterday in local time.
            $yesterdayStartUtc = $now->clone()->setTimezone($tz)->subDay()->startOfDay()->utc()->toDateTimeString();

            $total += ChildChallenge::query()
                ->whereIn('child_id', $this->childIdsByTimezone($tz))
                ->where('status', ChallengeStatus::IN_PROGRESS->value)
                ->whereNotNull('last_progress_at')
                ->where('last_progress_at', '<', $yesterdayStartUtc)
                ->update(['status' => ChallengeStatus::FAILED->value]);
        }

        return $total;
    }

    /**
     * Delete FAILED challenges for children whose local date has advanced past the failure day.
     * Per ТЗ §5: a failed challenge returns to "unselected" at 00:00 local time.
     */
    public function resetFailedChallengesAtMidnight(?CarbonInterface $now = null): int
    {
        $now ??= now();
        $total = 0;

        foreach ($this->distinctChildTimezones() as $tz) {
            $localMidnightUtc = $now->clone()->setTimezone($tz)->startOfDay()->utc()->toDateTimeString();

            $total += ChildChallenge::query()
                ->whereIn('child_id', $this->childIdsByTimezone($tz))
                ->where('status', ChallengeStatus::FAILED->value)
                ->where('updated_at', '<', $localMidnightUtc)
                ->delete();
        }

        return $total;
    }

    /**
     * Monthly reset: delete all COMPLETED, REWARD_CLAIMED, and FAILED challenge records.
     * Runs on the 1st of each month (UTC). Failed records are also cleaned up by the
     * nightly job, but we keep them here as a safety net.
     */
    public function resetMonthlyChallengeResults(?CarbonInterface $now = null): int
    {
        $now ??= now();

        if ((int) $now->day !== 1) {
            return 0;
        }

        return ChildChallenge::query()
            ->whereIn('status', [
                ChallengeStatus::COMPLETED->value,
                ChallengeStatus::REWARD_CLAIMED->value,
                ChallengeStatus::FAILED->value,
            ])
            ->delete();
    }

    public function cleanupOrphanMedia(int $olderThanHours = 24): int
    {
        return app(MediaService::class)->cleanupOrphans($olderThanHours);
    }

    /** @return Collection<int, string> */
    private function distinctChildTimezones(): Collection
    {
        return DB::table('profiles')
            ->where('role', 'child')
            ->whereNotNull('timezone')
            ->distinct()
            ->pluck('timezone');
    }

    private function childIdsByTimezone(string $timezone): Builder
    {
        return DB::table('profiles')
            ->select('user_id')
            ->where('role', 'child')
            ->where('timezone', $timezone);
    }
}
