<?php

namespace App\Http\Middleware;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\User;
use App\Support\Sandbox\SandboxManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $sandboxes = app(SandboxManager::class);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'role' => $user->role,
                ] : null,
                'can' => $user ? [
                    'archiveRecords' => Gate::forUser($user)->allows('archive-records'),
                    'decideDescriptions' => Gate::forUser($user)->allows('decide-descriptions'),
                    'manageCatalog' => Gate::forUser($user)->allows('manage-catalog'),
                ] : null,
            ],
            'demo' => fn () => [
                'inSandbox' => $sandboxes->active() !== null,
                'sandboxStartedAt' => $sandboxes->active() ? $sandboxes->createdAt($sandboxes->active())->toIso8601String() : null,
                'idleMinutes' => (int) config('demo.sandbox.idle_minutes'),
                'tour' => (bool) $request->session()->get('demo.tour'),
                'personas' => config('demo.personas'),
                'sourceUrl' => config('demo.source_url'),
                'portfolioUrl' => config('demo.portfolio_url'),
            ],
            'badges' => fn () => $user ? [
                'newInquiries' => Inquiry::where('status', InquiryStatus::New)->count(),
            ] : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
