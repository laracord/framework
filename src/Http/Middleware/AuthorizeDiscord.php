<?php

namespace Laracord\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class AuthorizeDiscord
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            abort_unless(auth()->user()->is_admin, 403, 'You are not authorized.');

            return $next($request);
        }

        $driver = Socialite::driver('discord')->setScopes(['identify'])->setRequest($request);

        return $driver->redirect();
    }
}
