<?php

namespace Laracord\Bot\Concerns;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Str;
use Laracord\Bot\Hook;
use Laracord\Http\HttpServer;

trait HasHttpServer
{
    /**
     * The HTTP server instance.
     */
    protected ?HttpServer $httpServer = null;

    /**
     * The HTTP server address.
     */
    protected ?string $httpAddress = null;

    /**
     * Determine if the HTTP server is enabled.
     */
    protected bool $httpEnabled = true;

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

        /** @var \Illuminate\Foundation\Configuration\Middleware $middleware */
        $middleware = $this->app->make(Middleware::class);

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
        if ($this->httpServer || ! $this->isHttpEnabled()) {
            return $this;
        }

        rescue(function () {
            $this->app->booted(function () {
                $this->app['router']->getRoutes()->refreshNameLookups();
                $this->app['router']->getRoutes()->refreshActionLookups();
            });

            $this->httpServer = HttpServer::make($this)
                ->setAddress($this->getHttpAddress())
                ->boot();

            if ($this->httpServer->isBooted()) {
                $this->logger->info("HTTP server started on <fg=blue>{$this->httpServer->getAddress()}</>.");

                $this->callHook(Hook::AFTER_HTTP_SERVER_START);
            }
        });

        return $this;
    }

    /**
     * Disable the HTTP server.
     */
    public function disableHttpServer(): self
    {
        $this->httpEnabled = false;

        return $this;
    }

    /**
     * Determine if the HTTP server is enabled.
     */
    public function isHttpEnabled(): bool
    {
        return $this->httpEnabled && $this->getHttpAddress();
    }

    /**
     * Set the HTTP server address.
     */
    public function setHttpAddress(string $address): self
    {
        $this->httpAddress = $address;

        return $this;
    }

    /**
     * Get the HTTP server address.
     */
    public function getHttpAddress(): ?string
    {
        $this->httpAddress ??= config('discord.http');

        if (! $this->httpAddress) {
            return null;
        }

        if (Str::startsWith($this->httpAddress, ':')) {
            $this->httpAddress = Str::start($this->httpAddress, '0.0.0.0');
        }

        $host = Str::before($this->httpAddress, ':');
        $port = Str::after($this->httpAddress, ':');

        if (! filter_var($host, FILTER_VALIDATE_IP)) {
            $this->logger->error('Invalid HTTP server address');

            return null;
        }

        if ($port > 65535 || $port < 1) {
            $this->logger->error('Invalid HTTP server port');

            return null;
        }

        return $this->httpAddress;
    }

    /**
     * Get the HTTP server instance.
     */
    public function httpServer(): ?HttpServer
    {
        return $this->httpServer;
    }
}
