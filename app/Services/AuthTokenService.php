<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AuthenticationException;
use App\Models\Auth\RefreshToken;
use App\Models\Auth\Session;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;
use Tymon\JWTAuth\JWTAuth;

class AuthTokenService
{
    public function __construct(
        private readonly JWTAuth $jwtAuth,
    ) {
    }

    public function issueTokens(User $user, array $context = []): array
    {
        return DB::transaction(function () use ($user, $context): array {

            $plainRefreshToken = bin2hex(random_bytes(64));
            $refreshExpiresAt = now()->addMinutes((int) config('jwt.refresh_ttl', 20160));
            $accessExpiresAt = now()->addMinutes((int) config('jwt.ttl', 60));

            $session = $user->sessions()->create([
                'device' => $this->resolveDevice($context['device'] ?? null, $context['user_agent'] ?? null),
                'ip_address' => $context['ip_address'] ?? null,
                'user_agent' => $context['user_agent'] ?? null,
                'last_seen_at' => now(),
            ]);

            $session->refreshTokens()->create([
                'token_hash' => hash('sha256', $plainRefreshToken),
                'expires_at' => $refreshExpiresAt,
            ]);

            $accessToken = $this->jwtAuth->claims(['sid' => $session->id])->fromUser($user);

            return [
                'session_id' => $session->id,
                'access_token' => $accessToken,
                'access_expires_at' => $accessExpiresAt,
                'refresh_token' => $plainRefreshToken,
                'refresh_expires_at' => $refreshExpiresAt,
            ];
        });
    }

    public function refreshAccessToken(string $plainRefreshToken): array
    {
        $tokenHash = hash('sha256', $plainRefreshToken);

        return DB::transaction(function () use ($tokenHash): array {

            /** @var RefreshToken|null $refreshToken */
            $refreshToken = RefreshToken::where('token_hash', $tokenHash)
                ->with('session.user')
                ->lockForUpdate()
                ->first();

            if (!$refreshToken instanceof RefreshToken) {
                throw AuthenticationException::sessionInactive();
            }

            $session = $refreshToken->session;

            if (!$session instanceof Session || $session->isRevoked() || !$refreshToken->isActive()) {
                throw AuthenticationException::sessionInactive();
            }

            $user = $session->user;

            if (!$user instanceof User) {
                throw AuthenticationException::required();
            }

            $nextRefreshToken = bin2hex(random_bytes(64));
            $nextRefreshTokenHash = hash('sha256', $nextRefreshToken);
            $accessExpiresAt = now()->addMinutes((int) config('jwt.ttl', 60));
            $accessToken = $this->jwtAuth->claims(['sid' => $session->id])->fromUser($user);

            $refreshToken->update(['used_at' => now()]);

            $session
                ->refreshTokens()
                ->whereKeyNot($refreshToken)
                ->whereNull('revoked_at')
                ->whereFuture('expires_at')
                ->update(['revoked_at' => now()]);

            $nextRefresh = $session->refreshTokens()->create([
                'token_hash' => $nextRefreshTokenHash,
                'expires_at' => $refreshToken->expires_at,
            ]);

            $session->update(['last_seen_at' => now()]);

            return [
                'session_id' => $session->id,
                'access_token' => $accessToken,
                'access_expires_at' => $accessExpiresAt,
                'refresh_token' => $nextRefreshToken,
                'refresh_expires_at' => $nextRefresh->expires_at,
            ];
        });
    }

    public function revokeSession(User $user, Session|string $sessionId): void
    {
        DB::transaction(function () use ($user, $sessionId): void {

            $user->sessions()
                ->whereKey($sessionId)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $user->refreshTokens()
                ->where('session_id', $sessionId instanceof Session ? $sessionId->getKey() : $sessionId)
                ->whereNull((new RefreshToken)->getTable() . '.revoked_at')
                ->update(['revoked_at' => now()]);
        });
    }

    public function revokeSessions(User $user): void
    {
        DB::transaction(function () use ($user): void {

            $user->sessions()
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $user->refreshTokens()
                ->whereNull((new RefreshToken)->getTable() . '.revoked_at')
                ->update(['revoked_at' => now()]);
        });
    }

    private function resolveDevice(?string $device, ?string $userAgent): ?string
    {
        if (filled($device)) {
            return mb_substr(trim($device), 0, 255);
        }

        if (blank($userAgent)) {
            return null;
        }

        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        if ($agent->isRobot()) {
            return null;
        }

        $deviceName = $agent->device() ?: null;
        $platform = $agent->platform() ?: null;
        $browser = $agent->browser() ?: null;

        // For desktop agents there is no specific device name - use "Desktop" as a label,
        // but only when we also have platform or browser to make the string meaningful.
        if ($deviceName === null && $agent->isDesktop() && ($platform !== null || $browser !== null)) {
            $deviceName = 'Desktop';
        }

        $parts = array_filter([$deviceName, $platform, $browser]);

        return filled($parts) ? implode(' / ', $parts) : null;
    }

}
