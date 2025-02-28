<?php

namespace Laracord\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

class FlushState
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(protected Application $app, protected ?Auth $auth = null)
    {
        //
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->auth) {
            foreach (array_keys(config('auth.guards', [])) as $guard) {
                $this->auth->guard($guard)->forgetUser();
            }
        }

        if ($this->app->resolved('cookie')) {
            $this->app->make('cookie')->flushQueuedCookies();
        }

        if ($this->app->resolved('session')) {
            with($this->app->make('session'), function ($session) {
                $session->flush();
                $session->regenerate();
            });
        }

        return $next($request);
    }
}
