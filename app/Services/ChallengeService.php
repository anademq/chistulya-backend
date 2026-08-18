<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ChallengeStatus;
use App\Models\Challenge;
use App\Models\ChallengeCategory;
use App\Models\Child\ChildChallenge;
use App\Models\User;
use App\Support\ExistingRecord;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChallengeService
{
    public const CATEGORIES_CACHE_KEY = 'categories:challenges';

    public function listAvailable(User $child, int $page, int $perPage = 10, ?int $categoryId = null): LengthAwarePaginator
    {
        return Challenge::availableFor($child)
            ->when($categoryId !== null, fn ($query) => $query->where('category_id', $categoryId))
            ->with(['category', 'media'])
            ->orderBy('title')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function listSelected(User $child, int $page, int $perPage = 10, ?int $categoryId = null): LengthAwarePaginator
    {
        $this->syncStaleStatuses($child);

        return ChildChallenge::query()
            ->with('challenge')
            ->where('child_id', $child->id)
            ->when($categoryId !== null, fn ($query) => $query->whereHas('challenge', fn ($challenge) => $challenge->where('category_id', $categoryId)))
            ->orderByDesc('updated_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function select(User $child, string $challengeId): ChildChallenge
    {
        $challenge = Challenge::whereKey($challengeId)->firstOrFail();

        $childChallenge = ChildChallenge::firstOrCreate(
            ['challenge_id' => $challenge->id, 'child_id' => $child->id],
            ['status' => ChallengeStatus::SELECTED, 'progress_days' => 0],
        );

        if ($childChallenge->wasRecentlyCreated) {
            app(AnalyticsService::class)->incrementChallengeSelected(
                $child,
                (int) $challenge->category_id,
                now(),
            );
        }

        return $childChallenge;
    }

    public function start(User $child, string $challengeId): ChildChallenge
    {
        $childChallenge = ExistingRecord::require(
            ChildChallenge::where('challenge_id', $challengeId)
                ->where('child_id', $child->id)
                ->first(),
            'challenge_id',
        );

        if (! $childChallenge->isSelected()) {
            throw ValidationException::withMessages([
                'challenge_id' => __('validation.in', ['attribute' => 'challenge_id']),
            ]);
        }

        $childChallenge->forceFill(['status' => ChallengeStatus::IN_PROGRESS])->save();

        return $childChallenge;
    }

    public function progress(User $child, string $challengeId): ChildChallenge
    {
        /** @var array{child_challenge:ChildChallenge,analytics:?array{type:string,category_id:int,date:Carbon}} $result */
        $result = DB::transaction(function () use ($child, $challengeId): array {
            $childChallenge = ExistingRecord::require(
                ChildChallenge::lockForUpdate()
                    ->where('challenge_id', $challengeId)
                    ->where('child_id', $child->id)
                    ->with('challenge')
                    ->first(),
                'challenge_id',
            );

            if (! $childChallenge->isInProgress()) {
                throw ValidationException::withMessages([
                    'challenge_id' => __('validation.in', ['attribute' => 'challenge_id']),
                ]);
            }

            $categoryId = (int) $childChallenge->challenge->category_id;

            if ($childChallenge->hasSkippedDay($this->childTimezone($child))) {
                $childChallenge->forceFill(['status' => ChallengeStatus::FAILED])->save();

                return [
                    'child_challenge' => $childChallenge,
                    'analytics' => [
                        'type' => 'failed',
                        'category_id' => $categoryId,
                        'date' => now(),
                    ],
                ];
            }

            $newProgress = $childChallenge->progress_days + 1;
            $target = (int) $childChallenge->challenge->duration_days;

            $payload = [
                'progress_days' => $newProgress,
                'last_progress_at' => now(),
            ];

            $analytics = null;
            $completedAt = null;

            if ($newProgress >= $target) {
                $completedAt = now();
                $payload['status'] = ChallengeStatus::COMPLETED;
                $payload['completed_at'] = $completedAt;
            }

            $childChallenge->forceFill($payload)->save();

            if ($completedAt !== null) {
                $analytics = [
                    'type' => 'completed',
                    'category_id' => $categoryId,
                    'date' => $completedAt,
                ];
            }

            return [
                'child_challenge' => $childChallenge,
                'analytics' => $analytics,
            ];
        });

        if ($result['analytics'] !== null) {
            $analytics = app(AnalyticsService::class);

            match ($result['analytics']['type']) {
                'failed' => $analytics->incrementChallengeFailed(
                    $child,
                    $result['analytics']['category_id'],
                    $result['analytics']['date'],
                ),
                'completed' => $analytics->incrementChallengeCompleted(
                    $child,
                    $result['analytics']['category_id'],
                    $result['analytics']['date'],
                ),
                default => null,
            };
        }

        if ($result['child_challenge']->isFailed()) {
            throw ValidationException::withMessages([
                'challenge_id' => __('validation.custom.challenge.skipped_day'),
            ]);
        }

        return $result['child_challenge'];
    }

    /**
     * @return array{child_challenge:ChildChallenge,reward:array{level:int,xp:int,coins:int,granted_xp:int,granted_coins:int}}
     */
    public function claim(User $child, string $challengeId): array
    {
        return DB::transaction(function () use ($child, $challengeId): array {
            $childChallenge = ExistingRecord::require(
                ChildChallenge::lockForUpdate()
                    ->where('challenge_id', $challengeId)
                    ->where('child_id', $child->id)
                    ->with('challenge')
                    ->first(),
                'challenge_id',
            );

            if (! $childChallenge->canClaimReward()) {
                throw ValidationException::withMessages([
                    'challenge_id' => __('validation.in', ['attribute' => 'challenge_id']),
                ]);
            }

            $challenge = $childChallenge->challenge;
            $reward = app(RewardService::class)->grant(
                $child,
                (int) $challenge->reward_xp,
                (int) $challenge->reward_coins,
            );

            $childChallenge->forceFill([
                'status' => ChallengeStatus::REWARD_CLAIMED,
                'reward_claimed_at' => now(),
            ])->save();

            return [
                'child_challenge' => $childChallenge,
                'reward' => $reward,
            ];
        });
    }

    /**
     * Lazily enforce timezone-aware stale states for a single child before returning their challenge list.
     * Also called from progress() via childTimezone(). Two queries: one UPDATE, one DELETE.
     */
    private function syncStaleStatuses(User $child): void
    {
        $tz = $this->childTimezone($child);
        $yesterdayStartUtc = now()->setTimezone($tz)->subDay()->startOfDay()->utc()->toDateTimeString();
        $localMidnightUtc = now()->setTimezone($tz)->startOfDay()->utc()->toDateTimeString();

        // Fail in-progress challenges that skipped a day in local time.
        ChildChallenge::query()
            ->where('child_id', $child->id)
            ->where('status', ChallengeStatus::IN_PROGRESS)
            ->whereNotNull('last_progress_at')
            ->where('last_progress_at', '<', $yesterdayStartUtc)
            ->update(['status' => ChallengeStatus::FAILED, 'updated_at' => now()]);

        // Remove failed challenges from a previous local day (return them to "unselected").
        ChildChallenge::query()
            ->where('child_id', $child->id)
            ->where('status', ChallengeStatus::FAILED)
            ->where('updated_at', '<', $localMidnightUtc)
            ->delete();
    }

    private function childTimezone(User $child): string
    {
        return (string) (DB::table('profiles')
            ->where('user_id', $child->id)
            ->value('timezone') ?? 'UTC');
    }

    // Category management (admin only)

    public function createCategory(array $data): ChallengeCategory
    {
        $category = ChallengeCategory::create([
            'slug' => $data['slug'],
            'title' => $data['title'],
            'order_column' => $data['order_column'] ?? null,
        ]);

        Cache::forget(self::CATEGORIES_CACHE_KEY);

        return $category;
    }

    public function updateCategory(ChallengeCategory $category, array $data): ChallengeCategory
    {
        $category->update(array_filter([
            'slug' => $data['slug'] ?? null,
            'title' => $data['title'] ?? null,
            'order_column' => $data['order_column'] ?? null,
        ], fn ($v) => $v !== null));

        Cache::forget(self::CATEGORIES_CACHE_KEY);

        return $category->fresh();
    }

    public function deleteCategory(ChallengeCategory $category): void
    {
        $category->delete();

        Cache::forget(self::CATEGORIES_CACHE_KEY);
    }
}
