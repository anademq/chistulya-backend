<?php

declare(strict_types=1);

namespace App\Models\Child;

use App\Enums\DailyTaskStatus;
use App\Models\DailyTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildDailyTask extends Model
{
    protected $table = 'child_daily_tasks';

    protected $primaryKey = null;

    public $incrementing = false;

    protected $fillable = [
        'daily_task_id',
        'child_id',
        'status',
        'completed_at',
        'reward_claimed_at',
    ];

    protected $casts = [
        'status' => DailyTaskStatus::class,
        'completed_at' => 'datetime',
        'reward_claimed_at' => 'datetime',
    ];

    public function dailyTask(): BelongsTo
    {
        return $this->belongsTo(DailyTask::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [DailyTaskStatus::COMPLETED, DailyTaskStatus::REWARD_CLAIMED], true);
    }

    public function isRewardClaimed(): bool
    {
        return $this->status === DailyTaskStatus::REWARD_CLAIMED;
    }

    public function canClaimReward(): bool
    {
        return $this->status === DailyTaskStatus::COMPLETED;
    }
}
