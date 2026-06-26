<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\ReminderNotificationSent;
use App\Models\Child\ChildReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Optimized reminder dispatch — runs every minute.
 *
 * Algorithm (no per-user loops):
 *   1. One query: distinct child timezones from profiles
 *   2. Per timezone: one UNION SQL → all (reminder, child) pairs for the current local HH:MM
 *   3. PHP filter: repeating_pattern / repeating_days / once-date
 *   4. One dedup query: already sent today
 *   5. Bulk INSERT + broadcast event per notification
 */
class SendReminderNotifications extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Dispatch reminder notifications respecting each child\'s local timezone (runs every minute).';

    private const ELIGIBLE_PAIRS_SQL = <<<'SQL'
        SELECT r.id               AS reminder_id,
               p.user_id          AS child_id,
               r.repeating_pattern,
               r.repeating_days,
               r.date,
               r.title,
               r.short_description,
               r.description,
               r.time,
               r.scope
        FROM reminders r
        CROSS JOIN profiles p
        WHERE r.status = 'active'
          AND r.time   = ?
          AND r.scope  = 'global'
          AND p.role   = 'child'
          AND p.timezone = ?

        UNION ALL

        SELECT r.id, p.user_id, r.repeating_pattern, r.repeating_days, r.date, r.title, r.short_description,
               r.description, r.time, r.scope
        FROM reminders r
        JOIN user_links ul ON ul.parent_id = r.created_by
        JOIN profiles   p  ON p.user_id    = ul.child_id
        WHERE r.status = 'active'
          AND r.time   = ?
          AND r.scope  = 'parent'
          AND p.role   = 'child'
          AND p.timezone = ?

        UNION ALL

        SELECT r.id, p.user_id, r.repeating_pattern, r.repeating_days, r.date, r.title, r.short_description,
               r.description, r.time, r.scope
        FROM reminders r
        JOIN reminder_assignments ra ON ra.reminder_id = r.id
        JOIN profiles             p  ON p.user_id      = ra.child_id
        WHERE r.status = 'active'
          AND r.time   = ?
          AND r.scope  = 'assigned'
          AND p.role   = 'child'
          AND p.timezone = ?

        UNION ALL

        SELECT r.id, p.user_id, r.repeating_pattern, r.repeating_days, r.date, r.title, r.short_description,
               r.description, r.time, r.scope
        FROM reminders r
        JOIN profiles p ON p.user_id = r.created_by
        WHERE r.status = 'active'
          AND r.time   = ?
          AND r.scope  = 'child'
          AND p.role   = 'child'
          AND p.timezone = ?
    SQL;

    public function handle(): int
    {
        $nowUtc = now()->utc();

        // One query: distinct timezones of active children
        $timezones = DB::table('profiles')
            ->where('role', 'child')
            ->whereNotNull('timezone')
            ->distinct()
            ->pluck('timezone');

        foreach ($timezones as $timezone) {
            $this->processTimezone($timezone, $nowUtc);
        }

        return self::SUCCESS;
    }

    private function processTimezone(string $timezone, Carbon $nowUtc): void
    {
        $localNow = $nowUtc->clone()->setTimezone($timezone);
        $localTime = $localNow->format('H:i');
        $localDate = $localNow->toDateString();
        $localDow = $localNow->dayOfWeekIso; // 1 = Mon, 7 = Sun

        // One UNION query → all eligible (reminder, child) pairs for this timezone + time
        $pairs = collect(
            DB::select(self::ELIGIBLE_PAIRS_SQL, [
                $localTime, $timezone,
                $localTime, $timezone,
                $localTime, $timezone,
                $localTime, $timezone,
            ])
        );

        if ($pairs->isEmpty()) {
            return;
        }

        // PHP-level filter: repeating pattern + day-of-week / once-date
        $due = $pairs->filter(fn (object $p) => $this->isDue($p, $localDow, $localDate));

        if ($due->isEmpty()) {
            return;
        }

        // Dedup: skip any pair already sent today (UTC day)
        $alreadySent = $this->fetchAlreadySentKeys($due, $nowUtc->toDateString());

        $toSend = $due->reject(
            fn (object $p) => isset($alreadySent["{$p->reminder_id}|{$p->child_id}"])
        );

        if ($toSend->isEmpty()) {
            return;
        }

        // Create notification records and broadcast
        foreach ($toSend as $pair) {
            $notification = ChildReminder::create([
                'reminder_id' => $pair->reminder_id,
                'child_id' => $pair->child_id,
                'sent_at' => $nowUtc,
            ]);

            event(new ReminderNotificationSent(
                notificationId: $notification->id,
                reminderId: $pair->reminder_id,
                childId: $pair->child_id,
                title: $pair->title,
                shortDescription: $pair->short_description,
                description: $pair->description,
                time: $pair->time,
                date: $pair->date,
                repeatingPattern: $pair->repeating_pattern,
                repeatingDays: $pair->repeating_days,
                scope: $pair->scope,
                sentAt: $nowUtc->toIso8601String(),
            ));
        }
    }

    /**
     * Returns true when this reminder should fire at the current local moment.
     */
    private function isDue(object $pair, int $localDow, string $localDate): bool
    {
        return match ($pair->repeating_pattern) {
            'daily' => true,
            'weekly' => filled($pair->repeating_days)
                && ($pair->repeating_days[$localDow - 1] ?? '0') === '1',
            'once' => $pair->date !== null && $pair->date === $localDate,
            default => false,
        };
    }

    /**
     * Returns a hash-set of "reminder_id|child_id" keys for pairs already sent today.
     *
     * @param  Collection<int, object>  $due
     * @return array<string, true>
     */
    private function fetchAlreadySentKeys(Collection $due, string $utcDate): array
    {
        $reminderIds = $due->pluck('reminder_id')->unique()->values()->all();
        $childIds = $due->pluck('child_id')->unique()->values()->all();

        $rows = DB::table('child_reminder_notifications')
            ->whereIn('reminder_id', $reminderIds)
            ->whereIn('child_id', $childIds)
            ->whereDate('sent_at', $utcDate)
            ->select('reminder_id', 'child_id')
            ->get();

        $keys = [];
        foreach ($rows as $row) {
            $keys["{$row->reminder_id}|{$row->child_id}"] = true;
        }

        return $keys;
    }
}
