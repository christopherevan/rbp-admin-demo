<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cloudflare Turnstile verification for the demo entry gate.
 *
 * Automatically inactive when either key is unset so local development and
 * automated tests work without external network calls.
 */
class Turnstile
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function enabled(): bool
    {
        return filled(config('services.turnstile.site_key'))
            && filled(config('services.turnstile.secret_key'));
    }

    public function siteKey(): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $key = config('services.turnstile.site_key');

        return is_string($key) ? $key : null;
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        $payload = [
            'secret' => (string) config('services.turnstile.secret_key'),
            'response' => $token,
        ];

        if (filled($ip)) {
            $payload['remoteip'] = $ip;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post(self::VERIFY_URL, $payload);

            return $response->successful() && (bool) $response->json('success');
        } catch (Throwable) {
            return false;
        }
    }
}
