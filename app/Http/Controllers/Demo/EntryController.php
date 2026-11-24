<?php

namespace App\Http\Controllers\Demo;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Sandbox\SandboxManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EntryController extends Controller
{
    public function __construct(private SandboxManager $sandboxes) {}

    /**
     * Enter the demo, as a persona (one click) or with a username and
     * password. This is the only route that creates a sandbox.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['nullable', Rule::enum(Role::class)],
            'username' => ['required_without:role', 'nullable', 'string', 'max:30'],
            'password' => ['required_with:username', 'nullable', 'string', 'max:100'],
            'website' => ['prohibited'], // honeypot
        ]);

        $created = $this->ensureSandbox();

        if (isset($data['role'])) {
            $user = User::where('username', config("demo.personas.{$data['role']}"))->firstOrFail();
        } else {
            $user = User::where('username', $data['username'])->first();

            if (! $user || ! Auth::getProvider()->validateCredentials($user, ['password' => $data['password']])) {
                if ($created) {
                    $this->sandboxes->destroy((string) $this->sandboxes->active());
                    Cookie::queue(Cookie::forget((string) config('demo.sandbox.cookie')));
                }

                throw ValidationException::withMessages(['username' => 'Those credentials don\'t match. Try demo / demo1234.']);
            }
        }

        $this->signIn($request, $user);

        if ($created) {
            $request->session()->flash('demo.tour', true);
        }

        return redirect()->route('dashboard');
    }

    /**
     * Swap between the Admin and Sales personas on the same sandbox, to see
     * the same records with each role's permissions.
     */
    public function switchRole(Request $request): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::enum(Role::class)]]);

        $user = User::where('username', config("demo.personas.{$data['role']}"))->firstOrFail();

        $this->signIn($request, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => "You're now {$user->name} ({$user->role->label()})."]);

        return back();
    }

    /** Sign out but keep the sandbox: entering again picks up where you left off. */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function ensureSandbox(): bool
    {
        if (! $this->sandboxes->enabled() || $this->sandboxes->active() !== null) {
            return false;
        }

        $id = $this->sandboxes->create();
        $this->sandboxes->activate($id);
        Cookie::queue($this->sandboxes->cookie($id));

        return true;
    }

    private function signIn(Request $request, User $user): void
    {
        Auth::login($user);

        $request->session()->regenerate();
        $request->session()->put('demo.username', $user->username);
        $request->session()->put('demo.sandbox', $this->sandboxes->active());
    }
}
