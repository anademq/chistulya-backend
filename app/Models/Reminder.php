<?php

namespace App\Models;

use App\Enums\ReminderRepeatPattern;
use App\Enums\ReminderScope;
use App\Enums\ReminderStatus;
use App\Models\Child\ChildReminder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reminder extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'created_by',
        'scope',
        'title',
        'short_description',
        'description',
        'date',
        'time',
        'repeating_pattern',
        'repeating_days',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'scope' => ReminderScope::class,
        'repeating_pattern' => ReminderRepeatPattern::class,
        'status' => ReminderStatus::class,
        'date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedChildren(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reminder_assignments', 'reminder_id', 'child_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(ChildReminder::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ReminderStatus::Active);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ReminderStatus::Completed);
    }

    public function isActive(): bool
    {
        return $this->status === ReminderStatus::Active;
    }

    public function isOneTime(): bool
    {
        return $this->repeating_pattern === ReminderRepeatPattern::ONCE;
    }

    public function firesOnDay(int $dayOfWeek): bool
    {
        if ($this->isOneTime() || ! $this->repeating_days) {
            return false;
        }

        return ($this->repeating_days[$dayOfWeek - 1] ?? '0') === '1';
    }
}
