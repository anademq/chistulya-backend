<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AuthorizationException;
use App\Models\LinkToken;
use App\Models\User;
use App\Models\User\UserLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FamilyService
{
    public function createChildLinkToken(User $child, ?int $ttlMinutes = 60): LinkToken
    {
        $this->assertChild($child);

        $token = Str::random(64);
        $expiresAt = filled($ttlMinutes) ? now()->addMinutes(max(1, (int) $ttlMinutes)) : null;

        $linkToken = $child->linkTokens()->create([
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
        ]);

        $linkToken->setAttribute('token', $token);

        return $linkToken;
    }

    public function linkChildByToken(User $parent, string $rawToken): UserLink
    {
        $this->assertParent($parent);

        $token = LinkToken::where('token_hash', hash('sha256', $rawToken))
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->first();

        if (! $token instanceof LinkToken || ! $token->isActive()) {
            throw ValidationException::withMessages(['token' => [(string) __('validation.custom.token.invalid_or_expired')]]);
        }

        $child = User::whereKey((string) $token->child_id)->first();
        if (! $child instanceof User) {
            throw ValidationException::withMessages(['token' => [(string) __('validation.custom.token.invalid_or_expired')]]);
        }

        $this->assertChild($child);

        if ($parent->is($child)) {
            throw ValidationException::withMessages(['token' => [(string) __('validation.custom.token.invalid_or_expired')]]);
        }

        return DB::transaction(function () use ($parent, $child, $token): UserLink {
            UserLink::firstOrCreate([
                'child_id' => $child->id,
                'parent_id' => $parent->id,
            ]);

            $token->forceFill(['used_at' => now()])->save();

            return UserLink::where('child_id', $child->id)
                ->where('parent_id', $parent->id)
                ->firstOrFail();
        });
    }

    public function unlinkChild(User $parent, string $childId): void
    {
        $this->assertParent($parent);

        UserLink::where('parent_id', $parent->id)
            ->where('child_id', $childId)
            ->delete();
    }

    public function assertParentAccessToChild(User $parent, string $childId): void
    {
        $this->assertParent($parent);

        $isLinked = UserLink::where('parent_id', $parent->id)
            ->where('child_id', $childId)
            ->exists();

        if (! $isLinked) {
            throw AuthorizationException::forbidden();
        }
    }

    public function assertChild(User $user): void
    {
        if (! $user->isChild()) {
            throw AuthorizationException::onlyChild();
        }
    }

    public function assertParent(User $user): void
    {
        if (! $user->isParent()) {
            throw AuthorizationException::onlyParent();
        }
    }
}
