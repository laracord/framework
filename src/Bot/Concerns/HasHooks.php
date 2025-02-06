<?php

namespace Laracord\Bot\Concerns;

trait HasHooks
{
    /**
     * Attempt to call the hook.
     */
    protected function callHook(string $hook): void
    {
        if (! method_exists($this, $hook)) {
            return;
        }

        $this->app->call([$this, $hook]);
    }
}
