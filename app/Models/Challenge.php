<?php

namespace App\Models;

use App\Enums\ChallengeScope;
use App\Models\Child\ChildChallenge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Challenge extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'created_by',
        'scope',
        'category_id',
        'title',
        'short_description',
        'description',
        'reward_xp',
        'reward_coins',
        'duration_days',
    ];

    protected $casts = [
        'scope' => ChallengeScope::class,
        'reward_xp' => 'integer',
        'reward_coins' => 'integer',
        'duration_days' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ChallengeCategory::class, 'category_id');
    }

    public function assignedChildren(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'challenge_assignments', 'challenge_id', 'child_id');
    }

    public function childChallenges(): HasMany
    {
        return $this->hasMany(ChildChallenge::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('order_column');
    }

    public function hasXpReward(): bool
    {
        return $this->reward_xp > 0;
    }

    public function hasCoinsReward(): bool
    {
        return $this->reward_coins > 0;
    }

    public function scopeAvailableFor(Builder $query, User $child): Builder
    {
        return $query
            ->where(fn (Builder $q) => $q
                ->where('scope', ChallengeScope::GLOBAL)
                ->orWhere(fn (Builder $s) => $s
                    ->where('scope', ChallengeScope::PARENT)
                    ->whereHas('creator', fn (Builder $c) => $c
                        ->whereHas('children', fn (Builder $ch) => $ch->where('users.id', $child->id))
                    )
                )
                ->orWhere(fn (Builder $s) => $s
                    ->where('scope', ChallengeScope::ASSIGNED)
                    ->whereHas('assignedChildren', fn (Builder $a) => $a->where('users.id', $child->id))
                )
            )
            ->whereDoesntHave('childChallenges', fn (Builder $q) => $q->where('child_id', $child->id));
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('scope', ChallengeScope::GLOBAL);
    }
}
