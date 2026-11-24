<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Sandbox\SandboxManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;

class ResetController extends Controller
{
    public function __construct(private SandboxManager $sandboxes) {}

    /**
     * Move the visitor onto a brand-new copy of the template rather than
     * overwriting their file in place: a request still writing to the old
     * file could leave a journal behind that SQLite would roll back into
     * the fresh copy.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        if ($this->sandboxes->enabled()) {
            $old = $this->sandboxes->active();

            $id = $this->sandboxes->create();
            $this->sandboxes->activate($id);
            Cookie::queue($this->sandboxes->cookie($id));
            $request->session()->put('demo.sandbox', $id);

            if ($old) {
                $this->sandboxes->destroy($old);
            }

            $user = User::where('username', $request->session()->get('demo.username'))->first();

            if ($user) {
                Auth::login($user);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'The demo has been reset to a fresh copy of the data.']);

        return redirect()->route('dashboard');
    }
}
