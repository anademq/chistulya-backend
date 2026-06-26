<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class CaptchaService
{
    public function assertValid(?string $token, ?string $ipAddress = null): void
    {
        $secret = (string) config('services.hcaptcha.secret');
        $enabled = (bool) config('services.hcaptcha.enabled', false);

        if (! $enabled) {
            return;
        }

        if (! $token) {
            throw ValidationException::withMessages([
                'captcha_token' => 'Требуется CAPTCHA',
            ]);
        }

        $response = Http::asForm()->post('https://hcaptcha.com/siteverify', [
            'secret' => $secret,
            'response' => $token,
            'remoteip' => $ipAddress,
        ])->json();

        if (! ($response['success'] ?? false)) {
            throw ValidationException::withMessages([
                'captcha_token' => 'CAPTCHA не пройдена',
            ]);
        }
    }
}
