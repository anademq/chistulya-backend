<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyTaskAnalytic extends Model
{
    protected $table = 'daily_task_analytics';

    protected $fillable = [
        'child_id',
        'category_id',
        'date',
        'selected_count',
        'completed_count',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'selected_count' => 'integer',
        'completed_count' => 'integer',
        'date' => 'date',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DailyTaskCategory::class, 'category_id');
    }
}
