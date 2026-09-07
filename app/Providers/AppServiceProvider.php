<?php

namespace App\Providers;

use App\Listeners\CheckApplicationHealth;
use App\Models\Station;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();

        Event::listen(DiagnosingHealth::class, CheckApplicationHealth::class);
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

        // Catches N+1s at development time rather than in production — see
        // plan/phases/phase-09-hardening-release.md M9.2. Never strict in
        // production: a missed eager-load there should degrade gracefully
        // (an extra query), not 500.
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Throttles dossier unlock attempts per IP + station, so a brute-force
     * pass at one station's password doesn't cost the attacker anything more
     * than a 429. See plan/phases/phase-03-dossier-shell.md M3.3.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('dossier-unlock', function (Request $request) {
            $station = $request->route('station');
            $stationKey = $station instanceof Station ? $station->public_id : (string) $station;

            return Limit::perMinute((int) config('dossier.unlock_throttle'))
                ->by($request->ip().'|'.$stationKey);
        });

        RateLimiter::for('dossier-download', function (Request $request) {
            return Limit::perMinute((int) config('dossier.downloads.download_throttle'))
                ->by((string) $request->ip());
        });
    }
}
