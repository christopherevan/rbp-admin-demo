<?php

namespace App\Http\Middleware;

use App\Support\Sandbox\SandboxManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Points the request at the visitor's own sandbox database.
 *
 * Runs right after StartSession and before anything that touches the
 * database: the auth guard's user lookup, route model binding, and the
 * shared Inertia props (see bootstrap/app.php).
 */
class DemoSandbox
{
    public function __construct(private SandboxManager $sandboxes) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->sandboxes->enabled()) {
            return $next($request);
        }

        $id = (string) $request->cookie((string) config('demo.sandbox.cookie'));

        // The session remembers the sandbox too, so a session replayed without
        // the cookie lands on the same copy instead of minting a new one per
        // request (which would skip the demo-enter rate limit).
        if (! $this->sandboxes->exists($id)) {
            $id = (string) $request->session()->get('demo.sandbox');
        }

        if ($this->sandboxes->exists($id)) {
            $this->sandboxes->activate($id);
        } elseif ($request->session()->has('demo.username')) {
            // Their sandbox was pruned while they were away. Seeding is
            // deterministic, so the same user ids exist in a fresh copy and
            // the session stays signed in.
            $id = $this->sandboxes->create();
            $this->sandboxes->activate($id);
            Cookie::queue($this->sandboxes->cookie($id));
            $request->session()->put('demo.sandbox', $id);

            Inertia::flash('toast', ['type' => 'info', 'message' => 'Your previous sandbox expired, so this is a fresh copy of the demo data.']);
        } else {
            // A first visit, or a crawler: no sandbox and no database. Only
            // POST /demo/enter creates one. Drop any stale login so the auth
            // guard never queries the empty placeholder connection.
            $this->sandboxes->deactivate();
            $request->session()->forget(Auth::guard()->getName());

            return $next($request);
        }

        // Resetting and signing out stay open: they're the way out of a full sandbox.
        if (! $request->isMethodSafe()
            && ! $request->routeIs('demo.reset', 'logout')
            && $this->sandboxes->size($id) > (int) config('demo.sandbox.max_bytes')) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This sandbox is full. Reset the demo to keep going.']);

            return back();
        }

        return $next($request);
    }
}
