<?php

declare(strict_types=1);

namespace App\Models\Child;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildDailyReward extends Model
{
    protected $table = 'child_daily_rewards';

    protected $primaryKey = 'child_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'child_id',
        'current_day',
        'last_claimed_at',
    ];

    protected $casts = [
        'current_day' => 'integer',
        'last_claimed_at' => 'datetime',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function wasClaimedToday(): bool
    {
        return $this->last_claimed_at?->isToday() ?? false;
    }

    public function isStreakBroken(): bool
    {
        if (! $this->last_claimed_at) {
            return false;
        }

        return $this->last_claimed_at->diffInDays(now()) > 1;
    }
}
