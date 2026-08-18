<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChallengeAnalytic;
use App\Models\DailyTaskAnalytic;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    // ─── Write: daily tasks ──────────────────────────────────────────────────

    public function incrementDailyTaskSelected(User $child, int $categoryId, Carbon $date): void
    {
        $this->atomicIncrement(DailyTaskAnalytic::class, [
            'child_id' => $child->id,
            'category_id' => $categoryId,
            'date' => $this->analyticsDate($child, $date),
        ], 'selected_count');
    }

    public function incrementDailyTaskCompleted(User $child, int $categoryId, Carbon $date): void
    {
        $this->atomicIncrement(DailyTaskAnalytic::class, [
            'child_id' => $child->id,
            'category_id' => $categoryId,
            'date' => $this->analyticsDate($child, $date),
        ], 'completed_count');
    }

    // ─── Write: challenges ───────────────────────────────────────────────────

    public function incrementChallengeSelected(User $child, int $categoryId, Carbon $date): void
    {
        $this->atomicIncrement(ChallengeAnalytic::class, [
            'child_id' => $child->id,
            'category_id' => $categoryId,
            'date' => $this->analyticsDate($child, $date),
        ], 'selected_count');
    }

    public function incrementChallengeCompleted(User $child, int $categoryId, Carbon $date): void
    {
        $this->atomicIncrement(ChallengeAnalytic::class, [
            'child_id' => $child->id,
            'category_id' => $categoryId,
            'date' => $this->analyticsDate($child, $date),
        ], 'completed_count');
    }

    public function incrementChallengeFailed(User $child, int $categoryId, Carbon $date): void
    {
        $this->atomicIncrement(ChallengeAnalytic::class, [
            'child_id' => $child->id,
            'category_id' => $categoryId,
            'date' => $this->analyticsDate($child, $date),
        ], 'failed_count');
    }

    // ─── Read: daily tasks ───────────────────────────────────────────────────

    /**
     * Daily task analytics for the last N days, optionally filtered by category id.
     *
     * Returns a dense series (every day included, zero-filled if no data).
     *
     * @return array<int, array{date:string,weekday:int,selected_count:int,completed_count:int}>
     */
    public function dailyTasksByLastDays(User $child, int $days = 30, ?int $categoryId = null): array
    {
        $days = max(1, min(90, $days));
        $now = $this->nowForChild($child);
        $start = $now->copy()->startOfDay()->subDays($days - 1);
        $end = $now->copy()->startOfDay();

        $dateKey = $this->dateKeyExpression('date');

        $query = DailyTaskAnalytic::query()
            ->where('child_id', $child->id)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->selectRaw("{$dateKey} as date_key, SUM(selected_count) as selected_count, SUM(completed_count) as completed_count")
            ->groupByRaw($dateKey);

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        $raw = $query->get()->keyBy(static fn ($row): string => (string) $row->date_key);

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
    }

    // ─── Read: challenges ────────────────────────────────────────────────────

    /**
     * Challenge analytics grouped by month for the last N months, optionally filtered by category id.
     *
     * Returns a dense series (every month included, zero-filled if no data).
     *
     * @return array<int, array{month:string,selected_count:int,completed_count:int,failed_count:int}>
     */
    public function challengesByLastMonths(User $child, int $months = 6, ?int $categoryId = null): array
    {
        $months = max(1, min(12, $months));
        $from = $this->nowForChild($child)->startOfMonth()->subMonths($months - 1);

        $monthKey = $this->monthFormat('date');

        $query = ChallengeAnalytic::query()
            ->where('child_id', $child->id)
            ->whereDate('date', '>=', $from->toDateString())
            ->selectRaw("{$monthKey} as month_key, SUM(selected_count) as selected_count, SUM(completed_count) as completed_count, SUM(failed_count) as failed_count")
            ->groupByRaw($monthKey);

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        $rows = $query->get()->keyBy(static fn ($row): string => (string) $row->month_key);

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
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array{child_id:string,category_id:int,date:string}  $where
     */
    private function atomicIncrement(string $modelClass, array $where, string $column): void
    {
        DB::transaction(function () use ($modelClass, $where, $column): void {
            /** @var DailyTaskAnalytic|ChallengeAnalytic|null $record */
            $record = $this->queryByUniqueKey($modelClass, $where)
                ->lockForUpdate()
                ->first();

            if ($record !== null) {
                $record->increment($column);

                return;
            }

            $payload = [
                ...$where,
                ...$this->zeroCounters($modelClass),
                $column => 1,
            ];

            try {
                $modelClass::query()->create($payload);
            } catch (QueryException $exception) {
                if (! $this->isUniqueViolation($exception)) {
                    throw $exception;
                }

                $this->queryByUniqueKey($modelClass, $where)
                    ->lockForUpdate()
                    ->first()
                    ?->increment($column);
            }
        });
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array{child_id:string,category_id:int,date:string}  $where
     * @return Builder<Model>
     */
    private function queryByUniqueKey(string $modelClass, array $where): Builder
    {
        return $modelClass::query()
            ->where('child_id', $where['child_id'])
            ->where('category_id', $where['category_id'])
            ->whereDate('date', $where['date']);
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<string, int>
     */
    private function zeroCounters(string $modelClass): array
    {
        return match ($modelClass) {
            DailyTaskAnalytic::class => [
                'selected_count' => 0,
                'completed_count' => 0,
            ],
            ChallengeAnalytic::class => [
                'selected_count' => 0,
                'completed_count' => 0,
                'failed_count' => 0,
            ],
            default => throw new \InvalidArgumentException("Unknown analytics model: {$modelClass}"),
        };
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $code = (string) $exception->getCode();

        if (in_array($code, ['23000', '23505'], true)) {
            return true;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate key')
            || str_contains($message, 'unique violation');
    }

    private function dateKeyExpression(string $column): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "{$column}::text",
            'sqlite' => "date({$column})",
            default => "DATE({$column})",
        };
    }

    private function monthFormat(string $column): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    private function childTimezone(User $child): string
    {
        return (string) (DB::table('profiles')
            ->where('user_id', $child->id)
            ->value('timezone') ?? config('app.timezone', 'UTC'));
    }

    private function nowForChild(User $child): Carbon
    {
        return now()->setTimezone($this->childTimezone($child));
    }

    private function analyticsDate(User $child, Carbon $moment): string
    {
        return $moment->copy()->setTimezone($this->childTimezone($child))->toDateString();
    }
}
