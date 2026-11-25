<?php

namespace App\Http\Middleware;

use App\Services\Turnstile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scripts only from this origin or carrying the per-request nonce (Vite's
 * tags and the inline theme script in app.blade.php).
 *
 * Styles allow 'unsafe-inline': the progress bar, toasts, dialogs, charts
 * and the @font-face block all inject <style> tags, and a nonce in style-src
 * would switch 'unsafe-inline' off.
 *
 * Skipped locally, where the Vite dev server is another origin and the
 * debug error page relies on inline scripts.
 */
class ContentSecurityPolicy
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isLocal()) {
            return $next($request);
        }

        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $turnstile = app(Turnstile::class)->enabled();
        $scriptSrc = "script-src 'self' 'nonce-{$nonce}'".($turnstile ? ' https://challenges.cloudflare.com' : '');

        $directives = [
            "default-src 'self'",
            $scriptSrc,
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        if ($turnstile) {
            $directives[] = "frame-src 'self' https://challenges.cloudflare.com";
        }

        $response->headers->set('Content-Security-Policy', implode('; ', $directives));

        return $response;
    }
}
