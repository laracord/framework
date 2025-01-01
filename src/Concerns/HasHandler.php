<?php

namespace Laracord\Concerns;

use Exception;

trait HasHandler
{
    /**
     * Resolve the handler using the container.
     */
    protected function resolveHandler(array $parameters = []): mixed
    {
        if (! method_exists($this, 'handle')) {
            $class = get_class($this);

            throw new Exception("{$class} must implement a handle method.");
        }

        return $this->bot->app->call([$this, 'handle'], $parameters);
    }
}
