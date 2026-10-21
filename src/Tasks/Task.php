<?php

namespace Laracord\Tasks;

use Laracord\Concerns\HasHandler;
use Laracord\HasLaracord;
use Laracord\Tasks\Contracts\Task as TaskContract;
use Laracord\Tasks\Exceptions\InvalidTaskInterval;
use React\EventLoop\TimerInterface;

abstract class Task implements TaskContract
{
    use HasHandler, HasLaracord;

    /**
     * The task name.
     */
    protected string $name = '';

    /**
     * The loop interval.
     */
    protected int $interval = 5;

    /**
     * Determine if the task handler should execute during boot.
     */
    protected bool $eager = false;

    /**
     * Determine if the task is enabled.
     */
    protected bool $enabled = true;

    /**
     * Determine if the task is booted.
     */
    protected bool $booted = false;

    /**
     * The timer for the task.
     */
    protected ?TimerInterface $timer = null;

    /**
     * Make a new task instance.
     */
    public static function make(): self
    {
        return new static;
    }

    /**
     * Boot the task.
     */
    public function boot(): self
    {
        if ($this->booted) {
            return $this;
        }

        if ($this->getInterval() < 1) {
            throw new InvalidTaskInterval($this->getName());
        }

        $this->timer = $this->bot()->getLoop()->addPeriodicTimer(
            $this->getInterval(),
            fn () => $this->resolveHandler()
        );

        if ($this->eager) {
            $this->bot->getLoop()->futureTick(fn () => $this->resolveHandler());
        }

        $this->bot()->logger->info("The <fg=blue>{$this->getName()}</> task has been booted.");

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
     * Get the task name.
     */
    public function getName(): string
    {
        if (filled($this->name)) {
            return $this->name;
        }

        return $this->name = class_basename(static::class);
    }

    /**
     * Determine if the task is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Determine if the task is booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Stop the task.
     */
    public function stop(): void
    {
        if (! $this->booted) {
            return;
        }

        $this->getLoop()->cancelTimer($this->timer);

        $this->bot()->logger->info("The <fg=blue>{$this->getName()}</> task has been stopped.");

        $this->timer = null;

        $this->booted = false;
    }
}
