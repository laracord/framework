<?php

namespace Laracord\Bot\Concerns;

use InvalidArgumentException;
use Laracord\Events\Event;

trait HasEvents
{
    /**
     * The registered events.
     */
    protected array $events = [];

    /**
     * Register the Discord events.
     */
    protected function bootEvents(): self
    {
        foreach ($this->events as $event) {
            if (! $event->isEnabled()) {
                continue;
            }

            $this->events[$event::class] = $event->register();

            $this->logger->info("The <fg=blue>{$event->getName()}</> event has been registered to <fg=blue>{$event->getHandler()}</>.");
        }

        return $this;
    }

    /**
     * Register an event.
     */
    public function registerEvent(Event|string $event): self
    {
        if (is_string($event)) {
            $event = $event::make();
        }

        if (! is_subclass_of($event, Event::class)) {
            $class = $event::class;

            throw new InvalidArgumentException("Class [{$class}] is not a valid event.");
        }

        $this->events[$event::class] = $event;

        return $this;
    }

    /**
     * Register multiple events.
     */
    public function registerEvents(array $events): self
    {
        foreach ($events as $event) {
            $this->registerEvent($event);
        }

        return $this;
    }

    /**
     * Discover events in a path.
     */
    public function discoverEvents(string $in, string $for): self
    {
        foreach ($this->discover(Event::class, $in, $for) as $event) {
            $this->registerEvent($event);
        }

        return $this;
    }

    /**
     * Get the registered events.
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * Get a registered event by name.
     */
    public function getEvent(string $name): ?Event
    {
        return $this->events[$name] ?? null;
    }
}
