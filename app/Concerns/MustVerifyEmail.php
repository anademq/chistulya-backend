<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Notifications\ActionMailNotification;
use App\Services\VerificationTokenService;

trait MustVerifyEmail
{
    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill(['email_verified_at' => $this->freshTimestamp()])->save();
    }

    public function markEmailAsUnverified(): bool
    {
        return $this->forceFill(['email_verified_at' => null])->save();
    }

    public function sendEmailVerificationNotification(): void
    {
        $token = app(VerificationTokenService::class)->issueEmailVerification($this);

        $url = rtrim((string) config('app.frontend_url'), '/')
            . config('app.frontend_routes.verify_email')
            . '?token=' . $token;

        $this->notify(new ActionMailNotification(
            subject: __('auth.notifications.verify_email.subject'),
            greeting: __('mail.greeting'),
            line: __('auth.notifications.verify_email.line'),
            actionText: __('auth.notifications.verify_email.action'),
            actionUrl: $url,
        ));
    }

    public function getEmailForVerification(): string
    {
        return $this->email;
    }
}
