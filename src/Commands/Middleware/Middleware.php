<?php

namespace Laracord\Commands\Middleware;

use Closure;

interface Middleware
{
    /**
     * Handle the command.
     *
     * @return mixed
     */
    public function handle(Context $context, Closure $next);
}
