<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyReward extends Model
{
    protected $primaryKey = 'day';

    public $incrementing = false;

    protected $fillable = [
        'day',
        'reward_xp',
        'reward_coins',
    ];

    protected $casts = [
        'day' => 'integer',
        'reward_xp' => 'integer',
        'reward_coins' => 'integer',
    ];

    public function hasXpReward(): bool
    {
        return $this->reward_xp > 0;
    }

    public function hasCoinsReward(): bool
    {
        return $this->reward_coins > 0;
    }
}
