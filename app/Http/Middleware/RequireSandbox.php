<?php

namespace App\Http\Middleware;

use App\Support\Sandbox\SandboxManager;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * For guest-facing pages that still write to a sandbox (the public quote
 * form): without one there is nowhere to put the data.
 */
class RequireSandbox
{
    public function __construct(private SandboxManager $sandboxes) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->sandboxes->enabled() && $this->sandboxes->active() === null) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Start the demo first: the quote form writes into your sandbox.']);

            return redirect()->route('home');
        }

        return $next($request);
    }
}
