<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeAnalytic extends Model
{
    protected $table = 'challenge_analytics';

    protected $fillable = [
        'child_id',
        'category_id',
        'date',
        'selected_count',
        'completed_count',
        'failed_count',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'selected_count' => 'integer',
        'completed_count' => 'integer',
        'failed_count' => 'integer',
        'date' => 'date',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ChallengeCategory::class, 'category_id');
    }
}
