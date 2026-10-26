<?php

namespace App\Providers;

use App\Models\BookingDetail;
use App\Models\TipeMobil;
use App\Observers\BookingDetailObserver;
use Cache;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use View;

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
        View::composer('*', function ($view) {
            $types = Cache::rememberForever('type', function () {
                return TipeMobil::all();
            });
            $view->with('type', $types);
        });
        Carbon::setLocale('id');

        // Batas percobaan login per kombinasi email+IP (anti brute-force).
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)
            ->by(strtolower((string) $r->input('email')) . '|' . $r->ip())
            ->response(function (Request $r, array $headers) {
                $msg = 'Terlalu banyak percobaan login. Coba lagi dalam 1 menit.';
                return $r->expectsJson()
                    ? response()->json(['status' => false, 'message' => $msg], 429, $headers)
                    : back()->withInput($r->only('email'))->withErrors(['email' => $msg]);
            }));
        // Endpoint login Google: batas per IP.
        RateLimiter::for('google-login', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));

        BookingDetail::observe(BookingDetailObserver::class);
    }
}
