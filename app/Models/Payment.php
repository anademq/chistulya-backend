<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentCurrency;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'payable_type',
        'payable_id',
        'method',
        'invoice_id',
        'currency',
        'amount',
        'status',
        'payload',
        'expires_at',
        'paid_at',
        'failure_reason',
        'failed_at',
    ];

    protected $casts = [
        'method' => PaymentMethod::class,
        'currency' => PaymentCurrency::class,
        'status' => PaymentStatus::class,
        'payload' => AsCollection::class,
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::PENDING)
            ->where('expires_at', '>', now());
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::PAID);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::FAILED);
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING && $this->expires_at->isFuture();
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::PAID;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    public function isExpired(): bool
    {
        return $this->status === PaymentStatus::EXPIRED || $this->expires_at->isPast();
    }
}
