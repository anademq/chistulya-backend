<?php

namespace App\Models;

use App\Casts\AsRequirements;
use App\Models\Child\ChildAchievement;
use App\Support\Requirements\AchievementRequirements;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property AchievementRequirements $requirements
 */
class Achievement extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'title',
        'short_description',
        'description',
        'is_available',
        'requirements',
        'reward_xp',
        'reward_coins',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'requirements' => AsRequirements::class . ':' . AchievementRequirements::class,
        'reward_xp' => 'integer',
        'reward_coins' => 'integer',
    ];

    public function childAchievements(): HasMany
    {
        return $this->hasMany(ChildAchievement::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('order_column');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    public function isAvailable(): bool
    {
        return (bool) $this->is_available;
    }
}
