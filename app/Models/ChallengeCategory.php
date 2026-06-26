<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChallengeCategory extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'order_column',
    ];

    protected $casts = [
        'order_column' => 'integer',
    ];

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class, 'category_id');
    }
}
