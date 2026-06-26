<?php

namespace App\Models;

use App\Enums\DailyTaskScope;
use App\Models\Child\ChildDailyTask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyTask extends Model
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
    ];

    protected $casts = [
        'scope' => DailyTaskScope::class,
        'reward_xp' => 'integer',
        'reward_coins' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DailyTaskCategory::class, 'category_id');
    }

    public function assignedChildren(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'daily_task_assignments', 'daily_task_id', 'child_id');
    }

    public function childDailyTasks(): HasMany
    {
        return $this->hasMany(ChildDailyTask::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('order_column');
    }

    public function isGlobal(): bool
    {
        return $this->scope === DailyTaskScope::GLOBAL;
    }

    public function isAssignedToChild(User $child): bool
    {
        return $this->assignedChildren()->where('users.id', $child->id)->exists();
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
        return $query->where(function (Builder $q) use ($child): void {
            $q->where('scope', DailyTaskScope::GLOBAL)
                ->orWhere(fn (Builder $s) => $s
                    ->where('scope', DailyTaskScope::PARENT)
                    ->whereHas('creator', fn (Builder $c) => $c
                        ->whereHas('children', fn (Builder $ch) => $ch->where('users.id', $child->id))
                    )
                )
                ->orWhere(fn (Builder $s) => $s
                    ->where('scope', DailyTaskScope::ASSIGNED)
                    ->whereHas('assignedChildren', fn (Builder $a) => $a->where('users.id', $child->id))
                );
        });
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('scope', DailyTaskScope::GLOBAL);
    }
}
