<?php

namespace App\Models;

use App\Models\Child\ChildPetItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PetItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'short_description',
        'description',
        'is_available',
        'requirements',
        'price',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'requirements' => 'array',
        'price' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PetItemCategory::class, 'category_id');
    }

    public function childPetItems(): HasMany
    {
        return $this->hasMany(ChildPetItem::class);
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

    public function isFree(): bool
    {
        return $this->price === 0;
    }
}
