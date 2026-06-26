<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    protected $primaryKey = 'child_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'child_id',
        'coins',
    ];

    protected $casts = [
        'coins' => 'integer',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function hasEnoughCoins(int $amount): bool
    {
        return $this->coins >= $amount;
    }

    public function debit(int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->decrement('coins', min($amount, $this->coins));
    }

    public function credit(int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->increment('coins', $amount);
    }
}
