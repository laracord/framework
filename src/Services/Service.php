<?php

namespace Laracord\Services;

use Laracord\Concerns\HasHandler;
use Laracord\HasLaracord;
use Laracord\Services\Contracts\Service as ServiceContract;
use Laracord\Services\Exceptions\InvalidServiceInterval;
use React\EventLoop\TimerInterface;

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
     * Determine if the service is booted.
     */
    protected bool $booted = false;

    /**
     * The timer for the service.
     */
    protected ?TimerInterface $timer = null;

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
        if ($this->booted) {
            return $this;
        }

        if ($this->getInterval() < 1) {
            throw new InvalidServiceInterval($this->getName());
        }

        if ($this->eager) {
            $this->resolveHandler();
        }

        $this->timer = $this->bot()->getLoop()->addPeriodicTimer(
            $this->getInterval(),
            fn () => $this->resolveHandler()
        );

        $this->logger->info("The <fg=blue>{$this->getName()}</> service has been booted.");

        $this->booted = true;

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

    /**
     * Determine if the service is booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Stop the service.
     */
    public function stop(): void
    {
        if (! $this->booted) {
            return;
        }

        $this->getLoop()->cancelTimer($this->timer);

        $this->logger->info("The <fg=blue>{$this->getName()}</> service has been stopped.");

        $this->timer = null;

        $this->booted = false;
    }
}
