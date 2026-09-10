<?php

namespace App\Providers;

use App\Support\MailFallback;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
        MailFallback::apply();
        RateLimiter::for('admin-login', fn (Request $request) => [
            Limit::perMinute(20)->by($request->ip()),
        ]);

        // Each public endpoint gets its own bucket. The anonymous `throttle:N,1`
        // form shares one counter per IP across every route, so a visitor using
        // the search could be blocked from sending the contact form.
        $formResponse = fn (string $field, string $bag = 'default') => fn (Request $request, array $headers) => $request->expectsJson()
            ? response()->json(['message' => 'Demasiados intentos. Probá nuevamente en unos minutos.'], 429, $headers)
            : back()->withInput($request->except(['password', 'password_confirmation']))->withErrors([$field => 'Demasiados intentos. Probá nuevamente en unos minutos.'], $bag);

        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perMinute(5)->by('contact-minute|'.$request->ip())->response($formResponse('message')),
            Limit::perHour(30)->by('contact-hour|'.$request->ip())->response($formResponse('message')),
        ]);
        RateLimiter::for('newsletter', fn (Request $request) => [
            Limit::perMinute(6)->by('newsletter-minute|'.$request->ip())->response($formResponse('newsletter_email', 'newsletter')),
            Limit::perHour(40)->by('newsletter-hour|'.$request->ip())->response($formResponse('newsletter_email', 'newsletter')),
        ]);
        RateLimiter::for('client-login', fn (Request $request) => [
            Limit::perMinute(5)->by('client-login-user|'.Str::lower((string) $request->input('username')).'|'.$request->ip())->response($formResponse('username')),
            Limit::perMinute(20)->by('client-login-ip|'.$request->ip())->response($formResponse('username')),
        ]);
        RateLimiter::for('client-register', fn (Request $request) => [
            Limit::perMinute(5)->by('client-register-minute|'.$request->ip())->response($formResponse('username')),
            Limit::perHour(20)->by('client-register-hour|'.$request->ip())->response($formResponse('username')),
        ]);
        RateLimiter::for('site-search', fn (Request $request) => [
            Limit::perMinute(60)->by($request->ip()),
        ]);
    }
}
