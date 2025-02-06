<?php

namespace Laracord\Bot\Concerns;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Configuration\Middleware;
use Laracord\Http\HttpServer;

trait HasHttpServer
{
    /**
     * The HTTP server instance.
     */
    protected ?HttpServer $httpServer = null;

    /**
     * The HTTP routes.
     */
    public function withRoutes(?callable $callback = null): self
    {
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make('router');

        if (! is_null($callback)) {
            $callback($router);
        }

        return $this;
    }

    /**
     * The HTTP middleware.
     */
    public function withMiddleware(?callable $callback = null): self
    {
        /** @var \Laracord\Http\Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);

        $middleware = new Middleware;

        if (! is_null($callback)) {
            $callback($middleware);
        }

        $kernel->setGlobalMiddleware($middleware->getGlobalMiddleware());
        $kernel->setMiddlewareGroups($middleware->getMiddlewareGroups());
        $kernel->setMiddlewareAliases($middleware->getMiddlewareAliases());

        if ($priorities = $middleware->getMiddlewarePriority()) {
            $kernel->setMiddlewarePriority($priorities);
        }

        return $this;
    }

    /**
     * Boot the HTTP server.
     */
    protected function bootHttpServer(): self
    {
        if ($this->httpServer) {
            return $this;
        }

        rescue(function () {
            $this->app->booted(function () {
                $this->app['router']->getRoutes()->refreshNameLookups();
                $this->app['router']->getRoutes()->refreshActionLookups();
            });

            $this->httpServer = HttpServer::make($this)->boot();

            if ($this->httpServer->isBooted()) {
                $this->logger->info("HTTP server started on <fg=blue>{$this->httpServer->getAddress()}</>.");
            }
        });

        return $this;
    }

    /**
     * Get the HTTP server instance.
     */
    public function httpServer(): ?HttpServer
    {
        return $this->httpServer;
    }
}
