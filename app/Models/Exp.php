<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exp extends Model
{
    protected $table = 'exps';

    protected $primaryKey = 'child_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'child_id',
        'level',
        'xp',
    ];

    protected $casts = [
        'level' => 'integer',
        'xp' => 'integer',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }
}
