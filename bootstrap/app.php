<?php

use App\Exceptions\WorkflowException;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\DemoSandbox;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireSandbox;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Only behind a reverse proxy (TRUSTED_PROXIES=10.0.0.2 or *); otherwise
        // a client could spoof X-Forwarded-For past the IP rate limits.
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : explode(',', $proxies));
        }

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            DemoSandbox::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            ContentSecurityPolicy::class,
        ]);

        // The sandbox needs the decrypted cookie and the session, and must be
        // in place before auth, route model binding and the shared props
        // touch the database.
        $middleware->appendToPriorityList(StartSession::class, DemoSandbox::class);

        $middleware->alias(['sandbox' => RequireSandbox::class]);

        $middleware->redirectTo(guests: '/', users: '/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A refused workflow step is a normal outcome, shown to the user as a
        // toast, not an error worth logging.
        $exceptions->dontReport(WorkflowException::class);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A denied action (e.g. Sales trying to invoice) comes back as a
        // toast on the page, with the policy's reason, instead of a 403 page.
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->isMethodSafe() || $request->expectsJson()) {
                return null;
            }

            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage() ?: 'You are not allowed to do that.']);

            return back();
        });
    })->create();
