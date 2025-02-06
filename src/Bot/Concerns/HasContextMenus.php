<?php

namespace Laracord\Bot\Concerns;

use InvalidArgumentException;
use Laracord\Commands\ContextMenu;

trait HasContextMenus
{
    /**
     * The registered context menus.
     */
    protected array $contextMenus = [];

    /**
     * Register a context menu.
     */
    public function registerContextMenu(ContextMenu|string $menu): self
    {
        if (is_string($menu)) {
            $menu = $menu::make();
        }

        if (! is_subclass_of($menu, ContextMenu::class)) {
            $class = $menu::class;

            throw new InvalidArgumentException("Class [{$class}] is not a valid context menu.");
        }

        $this->contextMenus[$menu::class] = $menu;

        return $this;
    }

    /**
     * Register multiple context menus.
     */
    public function registerContextMenus(array $menus): self
    {
        foreach ($menus as $menu) {
            $this->registerContextMenu($menu);
        }

        return $this;
    }

    /**
     * Discover context menus in a path.
     */
    public function discoverContextMenus(string $in, string $for): self
    {
        foreach ($this->discover(ContextMenu::class, $in, $for) as $menu) {
            $this->registerContextMenu($menu);
        }

        return $this;
    }

    /**
     * Get the registered context menus.
     */
    public function getContextMenus(): array
    {
        return $this->contextMenus;
    }

    /**
     * Get a registered context menu by name.
     */
    public function getContextMenu(string $name): ?ContextMenu
    {
        return $this->contextMenus[$name] ?? collect($this->contextMenus)->first(fn (ContextMenu $menu): bool => $menu->getName() === $name);
    }
}
