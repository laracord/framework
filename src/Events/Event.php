<?php

namespace Laracord\Events;

use Composer\InstalledVersions;
use Discord\DiscordCommandClient as Discord;
use Discord\WebSockets\Event as DiscordEvent;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laracord\Console\Commands\BootCommand as Console;
use Laracord\Laracord;

abstract class Event
{
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
     * The bot instance.
     *
     * @var \Laracord\Laracord
     */
    protected $bot;

    /**
     * The console instance.
     *
     * @var \Laracord\Console\Commands\BootCommand
     */
    protected $console;

    /**
     * The Discord instance.
     *
     * @var \Discord\DiscordCommandClient;
     */
    protected $discord;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Laracord $bot)
    {
        $this->bot = $bot;
        $this->console = $bot->console();
        $this->discord = $bot->discord();
    }

    /**
     * Make a new event instance.
     */
    public static function make(Laracord $bot): self
    {
        return new static($bot);
    }

    /**
     * Register the event.
     */
    public function register(): self
    {
        if (! $this->getHandler() || ! array_key_exists($this->getHandler(), $this->getEvents())) {
            $this->console()->error("The <fg=red>{$this->getName()}</> event handler <fg=red>{$this->getHandler()}</> is invalid.");

            return $this;
        }

        if (! method_exists($this, 'handle')) {
            $this->console()->error("The <fg=red>{$this->getName()}</> event handler does not have a handle method.");

            return $this;
        }

        $this->discord()->on($this->getHandler(), [$this, 'handle']);

        return $this;
    }

    /**
     * Build an embed for use in a Discord message.
     *
     * @param  string  $content
     * @return \Laracord\Discord\Message
     */
    public function message($content = '')
    {
        return $this->bot()->message($content);
    }

    /**
     * Get the Discord client.
     */
    public function discord(): Discord
    {
        return $this->discord;
    }

    /**
     * Get the bot instance.
     */
    public function bot(): Laracord
    {
        return $this->bot;
    }

    /**
     * Get the console instance.
     */
    public function console(): Console
    {
        return $this->console;
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
            ->mapWithKeys(fn ($file) => [Str::of($file->getFilename())->replace('.php', '')->__toString() => $file->getPathname()]);

        return collect($events)->mapWithKeys(function ($path, $event) use ($classes) {
            $class = Str::of($event)
                ->lower()
                ->replace('_', ' ')
                ->headline()
                ->replace(' ', '')
                ->__toString();

            if (! $classes->has($class)) {
                return [];
            }

            $name = Str::of($event)->lower()->replace('_', ' ')->headline()->__toString();

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
