<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyTaskCategory extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'order_column',
    ];

    protected $casts = [
        'order_column' => 'integer',
    ];

    public function dailyTasks(): HasMany
    {
        return $this->hasMany(DailyTask::class, 'category_id');
    }
}
