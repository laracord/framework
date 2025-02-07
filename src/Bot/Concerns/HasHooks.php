<?php

namespace Laracord\Bot\Concerns;

trait HasHooks
{
    /**
     * The registered hooks.
     */
    protected array $hooks = [];

    /**
     * Register a hook callback.
     */
    public function registerHook(string $hook, callable $callback): self
    {
        if (! isset($this->hooks[$hook])) {
            $this->hooks[$hook] = [];
        }

        $this->hooks[$hook][] = $callback;

        return $this;
    }

    /**
     * Call all registered callbacks for a hook.
     */
    protected function callHook(string $hook): void
    {
        foreach ($this->hooks[$hook] ?? [] as $callback) {
            $this->app->call($callback);
        }
    }
}
