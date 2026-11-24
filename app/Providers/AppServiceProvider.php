<?php

namespace App\Providers;

use App\Models\Inquiry;
use App\Models\Part;
use App\Models\Quotation;
use App\Models\User;
use App\Policies\QuotationPolicy;
use App\Support\Sandbox\SandboxManager;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\Response;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SandboxManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Relation::enforceMorphMap([
            'quotation' => Quotation::class,
            'inquiry' => Inquiry::class,
            'part' => Part::class,
        ]);
    }

    protected function configureAuthorization(): void
    {
        $adminOnly = fn (User $user) => $user->isAdmin()
            ? Response::allow()
            : Response::deny(QuotationPolicy::ADMIN_ONLY);

        // Archiving customers, deleting parts and units of measure.
        Gate::define('archive-records', $adminOnly);

        // Approving or rejecting AI-written catalog descriptions.
        Gate::define('decide-descriptions', $adminOnly);

        // Managing catalog product types and category mappings.
        Gate::define('manage-catalog', $adminOnly);
    }

    protected function configureRateLimiting(): void
    {
        // Entering the demo is the only thing that creates a sandbox.
        RateLimiter::for('demo-enter', fn (Request $request) => [
            Limit::perMinute(6)->by($request->ip()),
            Limit::perDay(40)->by($request->ip()),
        ]);

        RateLimiter::for('quote-request', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('sandbox-writes', function (Request $request) {
            if ($request->isMethodSafe()) {
                return Limit::none();
            }

            // By IP, not by sandbox: every reset hands out a new sandbox id.
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
