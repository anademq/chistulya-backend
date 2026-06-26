<?php

declare(strict_types=1);

namespace App\Models\Child\Assignment;

use App\Models\DailyTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyTaskAssignment extends Model
{
    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'daily_task_id',
        'child_id',
    ];

    public function dailyTask(): BelongsTo
    {
        return $this->belongsTo(DailyTask::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function isForChild(string $childId): bool
    {
        return $this->child_id === $childId;
    }
}


