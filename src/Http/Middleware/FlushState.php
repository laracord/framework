<?php

namespace Laracord\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

class FlushState
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * Create a new middleware instance.
     *
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
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
