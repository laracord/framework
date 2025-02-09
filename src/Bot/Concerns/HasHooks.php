<?php

namespace Laracord\Bot\Concerns;

use Laracord\Bot\Hook;

trait HasHooks
{
    /**
     * The registered hooks.
     *
     * @var array<string, array<callable>>
     */
    protected array $hooks = [];

    /**
     * Register a hook callback.
     */
    public function registerHook(Hook|string $hook, callable $callback): self
    {
        $hook = $hook instanceof Hook
            ? $hook->value
            : $hook;

        if (! isset($this->hooks[$hook])) {
            $this->hooks[$hook] = [];
        }

        $this->hooks[$hook][] = $callback;

        return $this;
    }

    /**
     * Call all registered callbacks for a hook.
     */
    public function callHook(Hook|string $hook): void
    {
        $hook = $hook instanceof Hook
            ? $hook->value
            : $hook;

        foreach ($this->hooks[$hook] ?? [] as $callback) {
            $this->app->call($callback);
        }
    }
}
