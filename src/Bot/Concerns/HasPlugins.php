<?php

namespace Laracord\Bot\Concerns;

use Laracord\Contracts\Plugin;

trait HasPlugins
{
    /**
     * The registered plugins.
     *
     * @var array<Plugin>
     */
    protected array $plugins = [];

    /**
     * Register a plugin.
     */
    public function plugin(Plugin $plugin): static
    {
        $plugin->register($this);

        $this->plugins[$plugin::class] = $plugin;

        return $this;
    }

    /**
     * Register multiple plugins.
     *
     * @param  array<Plugin>  $plugins
     */
    public function plugins(array $plugins): static
    {
        foreach ($plugins as $plugin) {
            $this->plugin($plugin);
        }

        return $this;
    }

    /**
     * Get the registered plugins.
     *
     * @return array<string, Plugin>
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Retrieve a registered plugin.
     */
    public function getPlugin(string $plugin): ?Plugin
    {
        return $this->plugins[$plugin] ?? null;
    }
}
