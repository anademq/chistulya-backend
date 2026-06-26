<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Child\ChildDailyReward;
use App\Models\DailyReward;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DailyRewardService
{
    public const CACHE_KEY = 'daily_rewards:all';

    /**
     * Claim the next daily reward for a child.
     *
     * Streak logic:
     *   - First claim or streak broken (missed a day) → resets to day 1.
     *   - Claimed yesterday → increments indefinitely (8, 9, 10 …).
     *   - If no reward row exists for the current day (i.e. beyond the configured
     *     streak length), falls back to the day-0 post-streak reward.
     *
     * @return array{day:int,reward:DailyReward,progress:ChildDailyReward,grant:array{level:int,xp:int,coins:int,granted_xp:int,granted_coins:int}}
     */
    public function claim(User $child): array
    {
        return DB::transaction(function () use ($child): array {
            $progress = ChildDailyReward::lockForUpdate()->firstOrCreate(
                ['child_id' => $child->id],
                ['current_day' => 1],
            );

            if ($progress->wasClaimedToday()) {
                throw ValidationException::withMessages([
                    'daily_reward' => __('validation.in', ['attribute' => 'daily_reward']),
                ]);
            }

            $nextDay = $this->resolveNextDay($progress);
            $reward = $this->findReward($nextDay);

            $grant = app(RewardService::class)->grant(
                $child,
                (int) $reward->reward_xp,
                (int) $reward->reward_coins,
            );

            $progress->forceFill([
                'current_day' => $nextDay,
                'last_claimed_at' => now(),
            ])->save();

            return [
                'day' => $nextDay,
                'reward' => $reward,
                'progress' => $progress,
                'grant' => $grant,
            ];
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Advance the streak counter, or reset to 1 if the streak is broken.
     */
    private function resolveNextDay(ChildDailyReward $progress): int
    {
        if (! $progress->last_claimed_at || ! $progress->last_claimed_at->isYesterday()) {
            return 1;
        }

        return $progress->current_day + 1;
    }

    /**
     * Find the reward for the given day.
     * Falls back to day 0 when no row exists for that day (post-streak).
     */
    private function findReward(int $day): DailyReward
    {
        /** @var Collection<int, DailyReward> $rewards */
        $rewards = Cache::remember(
            self::CACHE_KEY,
            now()->addDay(),
            static fn () => DailyReward::all()->keyBy('day'),
        );

        /** @var DailyReward|null $reward */
        $reward = $rewards->get($day);

        if ($reward instanceof DailyReward) {
            return $reward;
        }

        // Day beyond configured streak → give the post-streak (day 0) reward
        $fallback = $rewards->get(0) ?? DailyReward::where('day', 0)->firstOrFail();

        return $fallback;
    }
}
