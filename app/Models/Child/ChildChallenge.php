<?php

namespace App\Models\Child;

use App\Enums\ChallengeStatus;
use App\Models\Challenge;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildChallenge extends Model
{
    protected $table = 'child_challenges';

    protected $primaryKey = null;

    public $incrementing = false;

    protected $fillable = [
        'challenge_id',
        'child_id',
        'status',
        'progress_days',
        'last_progress_at',
        'completed_at',
        'reward_claimed_at',
    ];

    protected $casts = [
        'status' => ChallengeStatus::class,
        'progress_days' => 'integer',
        'last_progress_at' => 'datetime',
        'completed_at' => 'datetime',
        'reward_claimed_at' => 'datetime',
    ];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function isSelected(): bool
    {
        return $this->status === ChallengeStatus::SELECTED;
    }

    public function isInProgress(): bool
    {
        return $this->status === ChallengeStatus::IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return \in_array($this->status, [ChallengeStatus::COMPLETED, ChallengeStatus::REWARD_CLAIMED], true);
    }

    public function isFailed(): bool
    {
        return $this->status === ChallengeStatus::FAILED;
    }

    public function isRewardClaimed(): bool
    {
        return $this->status === ChallengeStatus::REWARD_CLAIMED;
    }

    public function canClaimReward(): bool
    {
        return $this->status === ChallengeStatus::COMPLETED;
    }

    public function hasSkippedDay(?string $timezone = null): bool
    {
        if (! $this->last_progress_at) {
            return false;
        }

        $tz = $timezone ?? config('app.timezone', 'UTC');
        $localNow = now()->setTimezone($tz);
        $localProgress = $this->last_progress_at->clone()->setTimezone($tz);

        return ! $localProgress->isSameDay($localNow)
            && ! $localProgress->isSameDay($localNow->copy()->subDay());
    }
}
