<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\VerificationTokenType;
use App\Exceptions\AuthenticationException;
use App\Models\Auth\VerificationToken;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class VerificationTokenService
{
    public function issueToken(User $user, VerificationTokenType $type, CarbonInterface $expiresAt): string
    {
        return DB::transaction(function () use ($user, $type, $expiresAt): string {
            $plainToken = bin2hex(random_bytes(64));

            $user->verificationTokens()
                ->where('type', $type)
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->whereFuture('expires_at')
                ->update(['revoked_at' => now()]);

            try {
                $user->verificationTokens()->create([
                    'type' => $type,
                    'token_hash' => hash('sha256', $plainToken),
                    'expires_at' => $expiresAt,
                ]);
            } catch (\Throwable) {
                throw AuthenticationException::tokenInvalid();
            }

            return $plainToken;
        });
    }

    public function consumeToken(VerificationTokenType $type, string $plainToken): VerificationToken
    {
        $tokenHash = hash('sha256', $plainToken);

        return DB::transaction(function () use ($type, $tokenHash): VerificationToken {
            /** @var VerificationToken|null $token */
            $token = VerificationToken::query()
                ->where('type', $type)
                ->where('token_hash', $tokenHash)
                ->with('user')
                ->lockForUpdate()
                ->first();

            if (! $token instanceof VerificationToken || ! $token->isActive()) {
                throw AuthenticationException::tokenInvalid();
            }

            $token->update(['used_at' => now()]);

            $token->user->verificationTokens()
                ->whereKeyNot($token)
                ->where('type', $type)
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->whereFuture('expires_at')
                ->update(['revoked_at' => now()]);

            return $token;
        });
    }

    public function issueEmailVerification(User $user): string
    {
        $ttl = (int) config('auth.verification.expire', 60);

        return $this->issueToken($user, VerificationTokenType::EMAIL_VERIFICATION, now()->addMinutes($ttl));
    }

    public function issuePasswordReset(User $user): string
    {
        $ttl = (int) config('auth.passwords.users.expire', 1440);

        return $this->issueToken($user, VerificationTokenType::PASSWORD_RESET, now()->addMinutes($ttl));
    }

    public function consumeEmailVerification(string $plainToken): VerificationToken
    {
        return $this->consumeToken(VerificationTokenType::EMAIL_VERIFICATION, $plainToken);
    }

    public function consumePasswordReset(string $plainToken): VerificationToken
    {
        return $this->consumeToken(VerificationTokenType::PASSWORD_RESET, $plainToken);
    }
}
