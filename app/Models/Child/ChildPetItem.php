<?php

declare(strict_types=1);

namespace App\Models\Child;

use App\Models\PetItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildPetItem extends Model
{
    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'pet_item_id',
        'child_id',
        'is_equipped',
        'purchased_at',
    ];

    protected $casts = [
        'is_equipped' => 'boolean',
        'purchased_at' => 'datetime',
    ];

    public function petItem(): BelongsTo
    {
        return $this->belongsTo(PetItem::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function isEquipped(): bool
    {
        return (bool) $this->is_equipped;
    }
}
