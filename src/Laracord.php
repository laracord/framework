<?php

namespace Laracord;

use Discord\DiscordCommandClient as Discord;
use Discord\WebSockets\Intents;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laracord\Commands\Command;
use Laracord\Console\Commands\Command as ConsoleCommand;
use Laracord\Logging\Logger;
use Laracord\Services\Service;
use React\EventLoop\Loop;
use ReflectionClass;

class Laracord
{
    /**
     * The event loop.
     */
    protected $loop;

    /**
     * The console instance.
     *
     * @var \Laracord\Console\Commands\Command
     */
    protected $console;

    /**
     * The Discord instance.
     *
     * @var \Discord\DiscordCommandClient
     */
    protected $discord;

    /**
     * The Discord bot name.
     */
    protected string $name = '';

    /**
     * The Discord bot description.
     */
    protected string $description = '';

    /**
     * The Discord bot token.
     */
    protected string $token = '';

    /**
     * The Discord bot command prefix.
     */
    protected string $prefix = '';

    /**
     * The Discord bot intents.
     */
    protected ?int $intents = null;

    /**
     * The DiscordPHP options.
     */
    protected array $options = [];

    /**
     * The Discord bot commands.
     */
    protected array $commands = [];

    /**
     * The bot services.
     */
    protected array $services = [];

    /**
     * The registered bot commands.
     */
    protected array $registeredCommands = [];

    /**
     * Initialize the Discord Bot.
     */
    public function __construct(ConsoleCommand $console)
    {
        $this->console = $console;
    }

    /**
     * Make the Bot instance.
     */
    public static function make(ConsoleCommand $console): self
    {
        return new static($console);
    }

    /**
     * Boot the bot.
     */
    public function boot(): void
    {
        if (! $this->getCommands()) {
            throw new Exception('You must register at least one Discord bot command.');
        }

        $this->beforeBoot();

        $this->bootDiscord();

        $this->discord()->on('ready', fn () => $this->afterBoot());

        $this->discord()->run();
    }

    /**
     * Boot the Discord client.
     */
    public function bootDiscord(): void
    {
        $this->discord = new Discord([
            'token' => $this->getToken(),
            'prefix' => $this->getPrefix(),
            'description' => $this->getDescription(),
            'discordOptions' => $this->getOptions(),
            'defaultHelpCommand' => false,
        ]);

        foreach ($this->getCommands() as $command) {
            $command = $command::make($this);

            $this->discord->registerCommand($command->getName(), fn ($message, $args) => $command->maybeHandle($message, $args), [
                'cooldown' => $command->getCooldown() ?: 0,
                'cooldownMessage' => $command->getCooldownMessage() ?: '',
                'description' => $command->getDescription() ?: '',
                'usage' => $command->getUsage() ?: '',
                'aliases' => $command->getAliases(),
            ]);

            $this->registeredCommands[] = $command;
        }
    }

    /**
     * Actions to run before booting the bot.
     */
    public function beforeBoot(): void
    {
        //
    }

    /**
     * Actions to run after booting the bot.
     */
    public function afterBoot(): void
    {
        $commands = count($this->registeredCommands);
        $commands = $commands === 1
            ? "<fg=blue>{$commands}</> command"
            : "<fg=blue>{$commands}</> commands";

        $this->console->log("Successfully booted <fg=blue>{$this->getName()}</> with {$commands}");

        $this->bootServices();
        $this->showCommands();
    }

    /**
     * Handle the bot services.
     */
    public function bootServices(): void
    {
        foreach ($this->getServices() as $service) {
            $service = $service::make($this);

            try {
                $service->boot();
            } catch (Exception $e) {
                $this->console->log("The <fg=red>{$service->getName()}</> service failed to boot.", 'error');
                $this->console->log($e->getMessage(), 'error');

                continue;
            }

            $this->console->log("The <fg=blue>{$service->getName()}</> service has been booted.");
        }
    }

    /**
     * Print the registered commands to console.
     */
    public function showCommands(): void
    {
        $this->console->table(
            ['<fg=blue>Command</>', '<fg=blue>Description</>'],
            collect($this->registeredCommands)->map(fn ($command) => [
                $command->getSignature(),
                $command->getDescription(),
            ])->toArray()
        );
    }

    /**
     * Get the bot name.
     */
    public function getName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        return $this->name = config('app.name');
    }

    /**
     * Get the bot description.
     */
    public function getDescription(): string
    {
        if ($this->description) {
            return $this->description;
        }

        return $this->description = config('discord.description');
    }

    /**
     * Get the bot token.
     */
    public function getToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        $token = config('discord.token');

        if (! $token) {
            throw new Exception('You must provide a Discord bot token.');
        }

        return $this->token = $token;
    }

    /**
     * Get the bot intents.
     */
    public function getIntents(): ?int
    {
        if ($this->intents) {
            return $this->intents;
        }

        return $this->intents = config('discord.intents', Intents::getDefaultIntents());
    }

    /**
     * Get the bot options.
     */
    public function getOptions(): array
    {
        if ($this->options) {
            return $this->options;
        }

        $defaultOptions = [
            'intents' => $this->getIntents(),
            'logger' => Logger::make($this->console),
            'loop' => $this->getLoop(),
        ];

        return $this->options = [
            ...config('discord.options', []),
            ...$defaultOptions,
        ];
    }

    /**
     * Get the bot services.
     */
    public function getServices(): array
    {
        if ($this->services) {
            return $this->services;
        }

        $services = $this->extractClasses($this->getServicePath())
            ->merge(config('discord.services', []))
            ->unique()
            ->filter(fn ($service) => is_subclass_of($service, Service::class) && ! (new ReflectionClass($service))->isAbstract())
            ->all();

        return $this->services = $services;
    }

    /**
     * Get the bot commands.
     */
    public function getCommands(): array
    {
        if ($this->commands) {
            return $this->commands;
        }

        $commands = $this->extractClasses($this->getCommandPath())
            ->merge(config('discord.commands', []))
            ->unique()
            ->filter(fn ($command) => is_subclass_of($command, Command::class) && ! (new ReflectionClass($command))->isAbstract())
            ->all();

        return $this->commands = $commands;
    }

    /**
     * Extract classes from the provided application path.
     */
    protected function extractClasses(string $path): Collection
    {
        return collect(File::allFiles($path))
            ->map(function ($file) {
                $relativePath = str_replace(
                    Str::finish(app_path(), DIRECTORY_SEPARATOR),
                    '',
                    $file->getPathname()
                );

                $folders = Str::beforeLast(
                    $relativePath,
                    DIRECTORY_SEPARATOR
                ).DIRECTORY_SEPARATOR;

                $className = Str::after($relativePath, $folders);

                $class = app()->getNamespace().str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    $folders.$className
                );

                return $class;
            });
    }

    /**
     * Get the registered commands.
     */
    public function getRegisteredCommands(): array
    {
        return $this->registeredCommands;
    }

    /**
     * Get the path to the Discord commands.
     */
    public function getCommandPath(): string
    {
        return app_path('Commands');
    }

    /**
     * Get the path to the bot services.
     */
    public function getServicePath(): string
    {
        return app_path('Services');
    }

    /**
     * Get the event loop.
     */
    public function getLoop()
    {
        if ($this->loop) {
            return $this->loop;
        }

        return $this->loop = Loop::get();
    }

    /**
     * Get the Discord instance.
     */
    public function discord(): Discord
    {
        return $this->discord;
    }

    /**
     * Get the console instance.
     */
    public function console(): ConsoleCommand
    {
        return $this->console;
    }

    /**
     * Retrieve the prefix.
     */
    public function getPrefix(): string
    {
        if ($this->prefix) {
            return $this->prefix;
        }

        return $this->prefix = config('discord.prefix');
    }
}
