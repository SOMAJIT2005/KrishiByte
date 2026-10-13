<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        foreach (['registration' => 5, 'tracking' => 30, 'reports' => 10, 'demo' => 20, 'decisions' => 30, 'confirmation' => 5, 'analytics' => 60, 'enrollment' => 5, 'ratings' => 5, 'purchases' => 10] as $name => $limit) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute($limit)->by($name.':'.$request->ip().':'.($request->user()?->id ?? 'guest')));
        }
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(10)->by('client:'.$request->ip()),
            Limit::perMinute(5)->by('account:'.hash('sha256', strtolower($request->input('email', '')).'|'.$request->ip())),
        ]);
    }
}
