<?php

namespace Laracord\Bot\Concerns;

use InvalidArgumentException;
use Laracord\Commands\Middleware\Middleware;

trait HasCommandMiddleware
{
    /**
     * The global command middleware.
     */
    protected array $commandMiddleware = [];

    /**
     * Register a global command middleware.
     */
    public function registerCommandMiddleware(string|Middleware $middleware): self
    {
        if (is_string($middleware)) {
            if (! class_exists($middleware)) {
                throw new InvalidArgumentException("Middleware class [{$middleware}] does not exist.");
            }

            if (! is_subclass_of($middleware, Middleware::class)) {
                throw new InvalidArgumentException("Middleware class [{$middleware}] must implement the Middleware interface.");
            }
        }

        $this->commandMiddleware[] = $middleware;

        return $this;
    }

    /**
     * Register multiple global command middleware.
     */
    public function registerCommandMiddlewares(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            $this->registerCommandMiddleware($middleware);
        }

        return $this;
    }

    /**
     * Get the global command middleware.
     */
    public function getCommandMiddleware(): array
    {
        return $this->commandMiddleware;
    }

    /**
     * Parse middleware string to get the name and parameters.
     */
    protected function parseMiddlewareString(string $middleware): array
    {
        [$name, $parameters] = array_pad(explode(':', $middleware, 2), 2, null);

        if (is_null($parameters)) {
            return [$name, []];
        }

        return [$name, explode(',', $parameters)];
    }

    /**
     * Get all middleware for a command, including global middleware.
     */
    public function resolveCommandMiddleware(array $commandMiddleware = []): array
    {
        $resolveMiddleware = function ($middleware) {
            if ($middleware instanceof Middleware) {
                return $middleware;
            }

            [$name, $parameters] = $this->parseMiddlewareString($middleware);

            if (empty($parameters)) {
                return new $name;
            }

            return new $name(...$parameters);
        };

        return array_map(
            $resolveMiddleware,
            array_merge($this->getCommandMiddleware(), $commandMiddleware)
        );
    }
}
