<?php

namespace Laracord\Providers;

use Exception;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        Route::middleware('web')->get('/auth/discord', function (Request $request) {
            if (auth()->check()) {
                return $request->user();
            }

            $secret = config('services.discord.client_secret');

            abort_if(! $secret, 403, 'You are not authorized.');

            $driver = Socialite::driver('discord')->setScopes(['identify'])->setRequest($request);

            try {
                $user = $driver->user();

                $model = config('auth.providers.users.model');

                if (! class_exists($model)) {
                    return redirect()->to('/');
                }

                $model = $model::firstOrCreate(['discord_id' => $user->id], [
                    'discord_id' => $user->id,
                    'username' => $user->nickname ?? $user->name,
                ]);

                auth()->login($model);

                return [
                    'user' => $user,
                ];
            } catch (Exception) {
                abort(403, 'You are not authorized.');
            }

            return redirect()->to('/');
        })->name('auth.discord');
    }
}
