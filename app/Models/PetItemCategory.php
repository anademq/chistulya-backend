<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PetItemCategory extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'order_column',
    ];

    protected $casts = [
        'order_column' => 'integer',
    ];

    public function petItems(): HasMany
    {
        return $this->hasMany(PetItem::class, 'category_id');
    }
}
