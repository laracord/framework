<?php

namespace Laracord\Bot\Concerns;

use InvalidArgumentException;
use Laracord\Services\Service;

trait HasServices
{
    /**
     * The registered services.
     */
    protected array $services = [];

    /**
     * Boot the bot services.
     */
    protected function bootServices(): self
    {
        foreach ($this->services as $service) {
            if (! $service->isEnabled()) {
                continue;
            }

            $this->services[$service::class] = $service->boot();

            $this->logger->info("The <fg=blue>{$service->getName()}</> service has been booted.");
        }

        return $this;
    }

    /**
     * Register a service.
     */
    public function registerService(Service|string $service): self
    {
        if (is_string($service)) {
            $service = $service::make();
        }

        if (! is_subclass_of($service, Service::class)) {
            $class = $service::class;

            throw new InvalidArgumentException("Class [{$class}] is not a valid service.");
        }

        $this->services[$service::class] = $service;

        return $this;
    }

    /**
     * Register multiple services.
     */
    public function registerServices(array $services): self
    {
        foreach ($services as $service) {
            $this->registerService($service);
        }

        return $this;
    }

    /**
     * Discover services in a path.
     */
    public function discoverServices(string $in, string $for): self
    {
        foreach ($this->discover(Service::class, $in, $for) as $service) {
            $this->registerService($service);
        }

        return $this;
    }

    /**
     * Get the registered services.
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * Get a registered service by name.
     */
    public function getService(string $name): ?Service
    {
        return $this->services[$name] ?? collect($this->services)->first(fn (Service $service): bool => $service->getName() === $name);
    }
}
