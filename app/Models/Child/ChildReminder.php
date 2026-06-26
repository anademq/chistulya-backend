<?php

namespace App\Models\Child;

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildReminder extends Model
{
    protected $table = 'child_reminder_notifications';

    public $timestamps = false;

    protected $fillable = [
        'reminder_id',
        'child_id',
        'sent_at',
        'seen_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'seen_at' => 'datetime',
    ];

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(Reminder::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function isSeen(): bool
    {
        return (bool) $this->seen_at;
    }

    public function markAsSeen(): void
    {
        $this->forceFill(['seen_at' => now()])->save();
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('seen_at');
    }
}
