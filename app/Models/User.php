<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProfileRole;
use App\Enums\UserRole;
use App\Models\Auth\RefreshToken;
use App\Models\Auth\Session;
use App\Models\Auth\VerificationToken;
use App\Models\Child\ChildAchievement;
use App\Models\Child\ChildChallenge;
use App\Models\Child\ChildDailyReward;
use App\Models\Child\ChildDailyTask;
use App\Models\Child\ChildPetItem;
use App\Models\Child\ChildReminder;
use App\Models\User\UserSubscription;
use App\Concerns\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasUuids, MustVerifyEmail, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'role',
        'email_verified_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
        'role' => UserRole::class,
        'email_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    // Auth

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function refreshTokens(): HasManyThrough
    {
        return $this->hasManyThrough(RefreshToken::class, Session::class, 'user_id', 'session_id');
    }

    public function verificationTokens(): HasMany
    {
        return $this->hasMany(VerificationToken::class);
    }

    // Linking

    public function linkTokens(): HasMany
    {
        return $this->hasMany(LinkToken::class, 'child_id');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_links', 'child_id', 'parent_id')
            ->withPivot('linked_at');
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_links', 'parent_id', 'child_id')
            ->withPivot('linked_at');
    }

    // Child stats

    public function exp(): HasOne
    {
        return $this->hasOne(Exp::class, 'child_id');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'child_id');
    }

    public function dailyReward(): HasOne
    {
        return $this->hasOne(ChildDailyReward::class, 'child_id');
    }

    // Child content

    public function childDailyTasks(): HasMany
    {
        return $this->hasMany(ChildDailyTask::class, 'child_id');
    }

    public function childChallenges(): HasMany
    {
        return $this->hasMany(ChildChallenge::class, 'child_id');
    }

    public function childAchievements(): HasMany
    {
        return $this->hasMany(ChildAchievement::class, 'child_id');
    }

    public function childPetItems(): HasMany
    {
        return $this->hasMany(ChildPetItem::class, 'child_id');
    }

    public function childReminders(): HasMany
    {
        return $this->hasMany(ChildReminder::class, 'child_id');
    }

    // Creator relations

    public function createdDailyTasks(): HasMany
    {
        return $this->hasMany(DailyTask::class, 'created_by');
    }

    public function createdChallenges(): HasMany
    {
        return $this->hasMany(Challenge::class, 'created_by');
    }

    public function createdReminders(): HasMany
    {
        return $this->hasMany(Reminder::class, 'created_by');
    }

    public function uploadedMedia(): HasMany
    {
        return $this->hasMany(Media::class, 'created_by');
    }

    // Subscription & payments

    public function userSubscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Helpers

    public function isAdminUser(): bool
    {
        return in_array($this->role, [UserRole::ADMIN, UserRole::SUDO_ADMIN], true);
    }

    public function isRegularUser(): bool
    {
        return $this->role === UserRole::USER;
    }

    public function isChild(): bool
    {
        $this->loadMissing('profile');

        return $this->profile?->role === ProfileRole::CHILD;
    }

    public function isParent(): bool
    {
        $this->loadMissing('profile');

        return $this->profile?->role === ProfileRole::PARENT;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->userSubscription()->active()->exists();
    }

    public function isEmailVerified(): bool
    {
        return (bool) $this->email_verified_at;
    }
}
