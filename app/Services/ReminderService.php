<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ReminderScope;
use App\Enums\ReminderStatus;
use App\Models\Child\ChildReminder;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReminderService
{
    private const MAX_COMPLETED_REMINDERS = 20;

    public function create(User $author, array $data): Reminder
    {
        return Reminder::create([
            'created_by' => $author->id,
            'scope' => $data['scope'],
            'title' => $data['title'],
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'repeating_pattern' => $data['repeating_pattern'],
            'date' => $data['date'] ?? null,
            'time' => $data['time'],
            'repeating_days' => $data['repeating_days'] ?? null,
        ]);
    }

    public function createForChild(User $author, User $child, array $data): Reminder
    {
        return DB::transaction(function () use ($author, $child, $data): Reminder {
            $reminder = $this->create($author, array_merge($data, ['scope' => ReminderScope::ASSIGNED]));

            $reminder->assignedChildren()->attach($child->id);

            return $reminder;
        });
    }

    public function createSelf(User $child, array $data): Reminder
    {
        return $this->create($child, array_merge($data, ['scope' => ReminderScope::CHILD]));
    }

    public function update(User $author, Reminder $reminder, array $data): Reminder
    {
        $reminder->update(array_filter([
            'title' => $data['title'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'repeating_pattern' => $data['repeating_pattern'] ?? null,
            'date' => $data['date'] ?? null,
            'time' => $data['time'] ?? null,
            'repeating_days' => $data['repeating_days'] ?? null,
        ], fn ($v) => $v !== null));

        return $reminder->fresh();
    }

    public function complete(Reminder $reminder): Reminder
    {
        $reminder->forceFill([
            'status' => ReminderStatus::Completed,
            'completed_at' => now(),
        ])->save();

        return $reminder;
    }

    public function delete(Reminder $reminder): void
    {
        $reminder->delete();
    }

    /**
     * Paginated list of reminders visible to a child (across all scopes).
     *
     * @param  bool  $completed  When true, returns completed reminders; otherwise returns active ones.
     */
    public function listForChild(
        User $child,
        bool $completed = false,
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $status = $completed ? ReminderStatus::Completed : ReminderStatus::Active;

        return Reminder::query()
            ->where('status', $status)
            ->where(function (Builder $q) use ($child): void {
                $q->where('scope', ReminderScope::GLOBAL)
                    ->orWhere(fn (Builder $s) => $s
                        ->where('scope', ReminderScope::PARENT)
                        ->whereHas('creator', fn (Builder $c) => $c
                            ->whereHas('children', fn (Builder $ch) => $ch->where('users.id', $child->id))
                        )
                    )
                    ->orWhere(fn (Builder $s) => $s
                        ->where('scope', ReminderScope::ASSIGNED)
                        ->whereHas('assignedChildren', fn (Builder $a) => $a->where('users.id', $child->id))
                    )
                    ->orWhere(fn (Builder $s) => $s
                        ->where('scope', ReminderScope::CHILD)
                        ->where('created_by', $child->id)
                    );
            })
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Re-activate a child's own completed reminder.
     */
    public function activateByChild(User $child, string $reminderId): Reminder
    {
        $reminder = Reminder::where('id', $reminderId)
            ->where('created_by', $child->id)
            ->firstOrFail();

        $reminder->forceFill([
            'status' => ReminderStatus::Active,
            'completed_at' => null,
        ])->save();

        return $reminder;
    }

    /**
     * Complete a child's own reminder, pruning completed ones beyond the 20-item cap.
     */
    public function completeByChild(User $child, string $reminderId): Reminder
    {
        $reminder = Reminder::where('id', $reminderId)
            ->where('created_by', $child->id)
            ->firstOrFail();

        $result = $this->complete($reminder);

        $this->pruneCompletedForChild($child);

        return $result;
    }

    private function pruneCompletedForChild(User $child): void
    {
        $count = Reminder::query()
            ->where('created_by', $child->id)
            ->where('status', ReminderStatus::Completed)
            ->count();

        if ($count <= self::MAX_COMPLETED_REMINDERS) {
            return;
        }

        $keepIds = Reminder::query()
            ->where('created_by', $child->id)
            ->where('status', ReminderStatus::Completed)
            ->orderByDesc('completed_at')
            ->limit(self::MAX_COMPLETED_REMINDERS)
            ->pluck('id');

        Reminder::query()
            ->where('created_by', $child->id)
            ->where('status', ReminderStatus::Completed)
            ->whereNotIn('id', $keepIds)
            ->forceDelete();
    }

    /**
     * Notification inbox for a child.
     *
     * @return Collection<int, ChildReminder>
     */
    public function listNotifications(User $child, bool $unseenOnly = false): Collection
    {
        $query = ChildReminder::query()
            ->with('reminder')
            ->where('child_id', $child->id)
            ->orderByDesc('sent_at');

        if ($unseenOnly) {
            $query->unread();
        }

        return $query->get();
    }

    public function markNotificationSeen(User $child, int $notificationId): ChildReminder
    {
        $notification = ChildReminder::where('id', $notificationId)
            ->where('child_id', $child->id)
            ->firstOrFail();

        if (! $notification->isSeen()) {
            $notification->markAsSeen();
        }

        return $notification;
    }

    public function assign(Reminder $reminder, User $child): void
    {
        $reminder->assignedChildren()->syncWithoutDetaching([$child->id]);
    }

    public function unassign(Reminder $reminder, User $child): void
    {
        $reminder->assignedChildren()->detach($child->id);
    }

    public function assertCanManage(User $user, Reminder $reminder): void
    {
        if ($reminder->created_by !== $user->id && ! $user->isAdminUser()) {
            throw ValidationException::withMessages([
                'reminder_id' => __('validation.exists', ['attribute' => 'reminder_id']),
            ]);
        }
    }
}
