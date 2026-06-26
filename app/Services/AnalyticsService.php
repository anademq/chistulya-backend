<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChallengeAnalytic;
use App\Models\DailyTaskAnalytic;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    private const CACHE_TTL_MINUTES = 10;

    // ─── Write: daily tasks ──────────────────────────────────────────────────

    public function incrementDailyTaskSelected(User $child, int $categoryId, Carbon $date): void
    {
        $this->atomicIncrement('daily_task_analytics', [
            'child_id' => $child->id,
            'category_id' => $categoryId,
            'date' => $date->toDateString(),
        ], 'selected_count');
    }

    public function incrementDailyTaskCompleted(User $child, int $categoryId, Carbon $date): void
    {
        $this->atomicIncrement('daily_task_analytics', [
            'child_id' => $child->id,
            'category_id' => $categoryId,
            'date' => $date->toDateString(),
        ], 'completed_count');
    }

    // ─── Write: challenges ───────────────────────────────────────────────────

    public function incrementChallengeSelected(User $child, int $categoryId, Carbon $date): void
    {
        $this->atomicIncrement('challenge_analytics', [
            'child_id' => $child->id,
            'category_id' => $categoryId,
            'date' => $date->toDateString(),
        ], 'selected_count');
    }

    public function incrementChallengeCompleted(User $child, int $categoryId, Carbon $date): void
    {
        $this->atomicIncrement('challenge_analytics', [
            'child_id' => $child->id,
            'category_id' => $categoryId,
            'date' => $date->toDateString(),
        ], 'completed_count');
    }

    public function incrementChallengeFailed(User $child, int $categoryId, Carbon $date): void
    {
        $this->atomicIncrement('challenge_analytics', [
            'child_id' => $child->id,
            'category_id' => $categoryId,
            'date' => $date->toDateString(),
        ], 'failed_count');
    }

    // ─── Read: daily tasks ───────────────────────────────────────────────────

    /**
     * Daily task analytics for the last N days, optionally filtered by category slug.
     *
     * Returns a dense series (every day included, zero-filled if no data).
     *
     * @return array<int, array{date:string,weekday:int,selected_count:int,completed_count:int}>
     */
    public function dailyTasksByLastDays(User $child, int $days = 30, ?string $categorySlug = null): array
    {
        $days = max(1, min(90, $days));
        $start = now()->startOfDay()->subDays($days - 1);
        $end = now()->endOfDay();

        $categoryId = $this->resolveTaskCategoryId($categorySlug);

        if ($categorySlug !== null && $categoryId === null) {
            return $this->emptyDaySeries($start, $days);
        }

        $cacheKey = sprintf('analytics:tasks:%s:%d:%s', $child->id, $days, $categoryId ?? 'all');

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($child, $start, $end, $categoryId, $days): array {
            $query = DailyTaskAnalytic::query()
                ->where('child_id', $child->id)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->selectRaw('date, SUM(selected_count) as selected_count, SUM(completed_count) as completed_count')
                ->groupBy('date');

            if ($categoryId !== null) {
                $query->where('category_id', $categoryId);
            }

            $raw = $query->get()->keyBy(fn ($row): string => Carbon::parse($row->date)->toDateString());

            $result = [];

            for ($i = 0; $i < $days; $i++) {
                $date = $start->copy()->addDays($i);
                $key = $date->toDateString();
                $row = $raw[$key] ?? null;

                $result[] = [
                    'date' => $key,
                    'weekday' => (int) $date->dayOfWeekIso,
                    'selected_count' => (int) ($row?->selected_count ?? 0),
                    'completed_count' => (int) ($row?->completed_count ?? 0),
                ];
            }

            return $result;
        });
    }

    // ─── Read: challenges ────────────────────────────────────────────────────

    /**
     * Challenge analytics grouped by month for the last N months, optionally filtered by category slug.
     *
     * Returns a dense series (every month included, zero-filled if no data).
     *
     * @return array<int, array{month:string,selected_count:int,completed_count:int,failed_count:int}>
     */
    public function challengesByLastMonths(User $child, int $months = 6, ?string $categorySlug = null): array
    {
        $months = max(1, min(12, $months));
        $from = now()->startOfMonth()->subMonths($months - 1);

        $categoryId = $this->resolveChallengesCategoryId($categorySlug);

        if ($categorySlug !== null && $categoryId === null) {
            return $this->emptyMonthSeries($from, $months);
        }

        $cacheKey = sprintf('analytics:challenges:%s:%d:%s', $child->id, $months, $categoryId ?? 'all');

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($child, $from, $months, $categoryId): array {
            $query = ChallengeAnalytic::query()
                ->where('child_id', $child->id)
                ->where('date', '>=', $from->toDateString())
                ->selectRaw($this->monthFormat('date') . " as month, SUM(selected_count) as selected_count, SUM(completed_count) as completed_count, SUM(failed_count) as failed_count")
                ->groupByRaw($this->monthFormat('date'));

            if ($categoryId !== null) {
                $query->where('category_id', $categoryId);
            }

            $rows = $query->get()->keyBy('month');

            $result = [];

            for ($i = 0; $i < $months; $i++) {
                $month = $from->copy()->addMonths($i)->format('Y-m');
                $row = $rows[$month] ?? null;

                $result[] = [
                    'month' => $month,
                    'selected_count' => (int) ($row?->selected_count ?? 0),
                    'completed_count' => (int) ($row?->completed_count ?? 0),
                    'failed_count' => (int) ($row?->failed_count ?? 0),
                ];
            }

            return $result;
        });
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Atomically increment a counter column, inserting the row if it doesn't exist.
     * Branches on DB driver so the same code runs on MySQL, PostgreSQL, and SQLite.
     *
     * @param  array<string, mixed>  $where  Values for the unique key columns
     */
    private function atomicIncrement(string $table, array $where, string $column): void
    {
        $cols = array_merge(array_keys($where), [$column, 'created_at', 'updated_at']);
        $placeholders = implode(', ', array_fill(0, count($where) + 1, '?')) . ', NOW(), NOW()';
        $values = [...array_values($where), 1];

        if (DB::getDriverName() === 'mysql') {
            $q = fn (string $c): string => '`' . $c . '`';
            $colList = implode(', ', array_map($q, $cols));
            DB::statement(
                "INSERT INTO {$q($table)} ({$colList}) VALUES ({$placeholders}) " .
                "ON DUPLICATE KEY UPDATE {$q($column)} = {$q($column)} + 1, `updated_at` = NOW()",
                $values,
            );
        } else {
            // PostgreSQL and SQLite both support the SQL-standard upsert syntax.
            $q = fn (string $c): string => '"' . $c . '"';
            $colList = implode(', ', array_map($q, $cols));
            $conflictCols = implode(', ', array_map($q, array_keys($where)));
            DB::statement(
                "INSERT INTO {$q($table)} ({$colList}) VALUES ({$placeholders}) " .
                "ON CONFLICT ({$conflictCols}) DO UPDATE SET {$q($column)} = {$q($column)} + 1, \"updated_at\" = NOW()",
                $values,
            );
        }
    }

    private function monthFormat(string $column): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    private function resolveTaskCategoryId(?string $slug): ?int
    {
        if ($slug === null) {
            return null;
        }

        return (int) Cache::remember(
            "analytics:category:task:{$slug}",
            now()->addHour(),
            fn (): mixed => DB::table('daily_task_categories')->where('slug', $slug)->value('id'),
        ) ?: null;
    }

    private function resolveChallengesCategoryId(?string $slug): ?int
    {
        if ($slug === null) {
            return null;
        }

        return (int) Cache::remember(
            "analytics:category:challenge:{$slug}",
            now()->addHour(),
            fn (): mixed => DB::table('challenge_categories')->where('slug', $slug)->value('id'),
        ) ?: null;
    }

    /**
     * @return array<int, array{date:string,weekday:int,selected_count:int,completed_count:int}>
     */
    private function emptyDaySeries(Carbon $start, int $days): array
    {
        $result = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);

            $result[] = [
                'date' => $date->toDateString(),
                'weekday' => (int) $date->dayOfWeekIso,
                'selected_count' => 0,
                'completed_count' => 0,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{month:string,selected_count:int,completed_count:int,failed_count:int}>
     */
    private function emptyMonthSeries(Carbon $from, int $months): array
    {
        $result = [];

        for ($i = 0; $i < $months; $i++) {
            $result[] = [
                'month' => $from->copy()->addMonths($i)->format('Y-m'),
                'selected_count' => 0,
                'completed_count' => 0,
                'failed_count' => 0,
            ];
        }

        return $result;
    }
}
