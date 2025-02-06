<?php

namespace Laracord\Events;

use Composer\InstalledVersions;
use Discord\WebSockets\Event as DiscordEvent;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laracord\HasLaracord;

abstract class Event
{
    use HasLaracord;

    /**
     * The event name.
     *
     * @var string
     */
    protected $name;

    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = DiscordEvent::READY;

    /**
     * Determine if the event is enabled.
     *
     * @var bool
     */
    protected $enabled = true;

    /**
     * Make a new event instance.
     */
    public static function make(): self
    {
        return new static;
    }

    /**
     * Register the event.
     */
    public function register(): self
    {
        if (! $this->getHandler() || ! array_key_exists($this->getHandler(), $this->getEvents())) {
            $this->logger()->error("The <fg=red>{$this->getName()}</> event handler <fg=red>{$this->getHandler()}</> is invalid.");

            return $this;
        }

        if (! method_exists($this, 'handle')) {
            $this->logger()->error("The <fg=red>{$this->getName()}</> event handler does not have a handle method.");

            return $this;
        }

        $this->discord()->on($this->getHandler(), [$this, 'handle']);

        return $this;
    }

    /**
     * Get the available events.
     */
    public static function getEvents(): array
    {
        $events = (new \ReflectionClass(DiscordEvent::class))->getConstants();

        $source = InstalledVersions::getInstallPath('team-reflex/discord-php');
        $source = Str::finish($source, '/src/Discord/WebSockets/Events');

        if (! File::isDirectory($source)) {
            return [];
        }

        $classes = collect(File::allFiles($source))
            ->mapWithKeys(fn ($file) => [Str::of($file->getFilename())->replace('.php', '')->toString() => $file->getPathname()]);

        return collect($events)->mapWithKeys(function ($path, $event) use ($classes) {
            $class = Str::of($event)
                ->lower()
                ->replace('_', ' ')
                ->headline()
                ->replace(' ', '')
                ->toString();

            if (! $classes->has($class)) {
                return [];
            }

            $name = Str::of($event)
                ->lower()
                ->replace('_', ' ')
                ->headline()
                ->toString();

            return [$event => [
                'key' => $event,
                'name' => $name,
                'class' => $class,
                'path' => $classes->get($class),
            ]];
        })->filter()->toArray();
    }

    /**
     * Get the event name.
     */
    public function getName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        return $this->name = class_basename(static::class);
    }

    /**
     * Determine if the event is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get the event handler.
     */
    public function getHandler(): string
    {
        return $this->handler;
    }
}
