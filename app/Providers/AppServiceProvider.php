<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
     * Configure application rate limiters.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('dahua-webhook', function (Request $request): array {
            $username = $request->getUser();
            $cameraId = $request->query('camera');

            $usernameKey = is_string($username) && $username !== ''
                ? hash('sha256', $username)
                : (is_string($cameraId) && ctype_digit($cameraId) ? 'camera:' . $cameraId : 'missing');

            return [
                Limit::perMinute((int) config('services.dahua.webhook_ip_limit_per_minute'))
                    ->by('dahua-webhook:ip:' . $request->ip()),
                Limit::perMinute((int) config('services.dahua.webhook_username_limit_per_minute'))
                    ->by('dahua-webhook:username:' . $usernameKey),
            ];
        });
    }
}
