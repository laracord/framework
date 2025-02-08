<?php

namespace Laracord\Services;

use Laracord\Concerns\HasHandler;
use Laracord\HasLaracord;
use Laracord\Services\Contracts\Service as ServiceContract;
use Laracord\Services\Exceptions\InvalidServiceInterval;

abstract class Service implements ServiceContract
{
    use HasHandler, HasLaracord;

    /**
     * The service name.
     */
    protected string $name = '';

    /**
     * The loop interval.
     */
    protected int $interval = 5;

    /**
     * Determine if the service handler should execute during boot.
     */
    protected bool $eager = false;

    /**
     * Determine if the service is enabled.
     */
    protected bool $enabled = true;

    /**
     * Make a new service instance.
     */
    public static function make(): self
    {
        return new static;
    }

    /**
     * Boot the service.
     */
    public function boot(): self
    {
        if ($this->getInterval() < 1) {
            throw new InvalidServiceInterval($this->getName());
        }

        if ($this->eager) {
            $this->resolveHandler();
        }

        $this->bot()->getLoop()->addPeriodicTimer(
            $this->getInterval(),
            fn () => $this->resolveHandler()
        );

        return $this;
    }

    /**
     * Get the loop instance.
     */
    public function getLoop()
    {
        return $this->bot()->getLoop();
    }

    /**
     * Get the loop interval.
     */
    public function getInterval(): int
    {
        return $this->interval;
    }

    /**
     * Set the loop interval.
     */
    public function interval(int $interval): self
    {
        $this->interval = $interval;

        return $this;
    }

    /**
     * Get the service name.
     */
    public function getName(): string
    {
        if (filled($this->name)) {
            return $this->name;
        }

        return $this->name = class_basename(static::class);
    }

    /**
     * Determine if the service is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
