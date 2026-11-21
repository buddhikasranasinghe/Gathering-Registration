<?php

declare(strict_types=1);

namespace App\Foundation\Providers;

use App\Identity\IO\Http\OtpSession;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        $this->configureOtpRequestLimiter();
        $this->configureOtpVerifyLimiter();
    }

    /**
     * Throttle passcode requests by email and by IP.
     */
    private function configureOtpRequestLimiter(): void
    {
        RateLimiter::for('otp-request', function (Request $request): array {
            $email = Str::lower((string) ($request->input('email') ?? OtpSession::email($request->session()) ?? ''));
            $emailKey = $email !== '' ? $email : $request->ip();

            return [
                Limit::perMinutes(10, 5)->by('otp-request:email:'.$emailKey),
                Limit::perMinutes(10, 10)->by('otp-request:ip:'.$request->ip()),
            ];
        });
    }

    /**
     * Throttle verification attempts by the pending email address and client IP.
     */
    private function configureOtpVerifyLimiter(): void
    {
        RateLimiter::for('otp-verify', function (Request $request): Limit {
            $email = Str::lower((string) OtpSession::email($request->session()));

            return Limit::perMinute(5)->by('otp-verify:'.$email.'|'.$request->ip());
        });
    }
}
