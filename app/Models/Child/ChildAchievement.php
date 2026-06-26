<?php

declare(strict_types=1);

namespace App\Models\Child;

use App\Enums\AchievementStatus;
use App\Models\Achievement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildAchievement extends Model
{
    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'achievement_id',
        'child_id',
        'status',
        'completed_at',
        'reward_claimed_at',
    ];

    protected $casts = [
        'status' => AchievementStatus::class,
        'completed_at' => 'datetime',
        'reward_claimed_at' => 'datetime',
    ];

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [AchievementStatus::COMPLETED, AchievementStatus::REWARD_CLAIMED], true);
    }

    public function isRewardClaimed(): bool
    {
        return $this->status === AchievementStatus::REWARD_CLAIMED;
    }

    public function canClaimReward(): bool
    {
        return $this->status === AchievementStatus::COMPLETED;
    }
}
