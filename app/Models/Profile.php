<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProfileRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'name',
        'sex',
        'role',
        'date_of_birth',
        'city',
        'timezone',
    ];

    protected $casts = [
        'sex' => 'boolean',
        'role' => ProfileRole::class,
        'date_of_birth' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isChild(): bool
    {
        return $this->role === ProfileRole::CHILD;
    }

    public function isParent(): bool
    {
        return $this->role === ProfileRole::PARENT;
    }
}
