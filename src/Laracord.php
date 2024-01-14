<?php

namespace Laracord;

use Discord\DiscordCommandClient as Discord;
use Discord\WebSockets\Intents;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laracord\Commands\Command;
use Laracord\Logging\Logger;
use LaravelZero\Framework\Commands\Command as LaravelCommand;
use ReflectionClass;

class Laracord
{
    /**
     * The Command instance.
     *
     * @var \LaravelZero\Framework\Commands\Command
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
     * The registered bot commands.
     */
    protected array $registeredCommands = [];

    /**
     * Initialize the Discord Bot.
     */
    public function __construct(LaravelCommand $console)
    {
        $this->console = $console;
    }

    /**
     * Make the Bot instance.
     */
    public static function make(LaravelCommand $console): self
    {
        return new static($console);
    }

    /**
     * Boot the bot.
     */
    public function boot(): void
    {
        if (! $this->getToken()) {
            throw new Exception('You must provide a Discord bot token.');
        }

        if (! $this->getCommands()) {
            throw new Exception('You must register at least one Discord bot command.');
        }

        $this->discord = new Discord([
            'token' => $this->getToken(),
            'prefix' => $this->getPrefix(),
            'description' => $this->getDescription(),
            'discordOptions' => $this->getOptions(),
            'defaultHelpCommand' => false,
        ]);

        foreach ($this->getCommands() as $command) {
            $command = new $command($this);

            $this->discord->registerCommand($command->getName(), fn ($message, $args) => $command->maybeHandle($message, $args), [
                'cooldown' => $command->getCooldown() ?: 0,
                'cooldownMessage' => $command->getCooldownMessage() ?: '',
                'description' => $command->getDescription() ?: '',
                'usage' => $command->getUsage() ?: '',
                'aliases' => $command->getAliases(),
            ]);

            $this->registeredCommands[] = $command;
        }

        $this->discord->on('ready', function ($discord) {
            $commands = count($this->registeredCommands);
            $commands = $commands === 1
                ? "<fg=blue>{$commands}</> command"
                : "<fg=blue>{$commands}</> commands";

            $this->console->log("Successfully booted <fg=blue>{$this->getName()}</> with {$commands}");

            $this->console->table(
                ['<fg=blue>Command</>', '<fg=blue>Description</>'],
                collect($this->registeredCommands)->map(fn ($command) => [
                    $command->getSyntax(),
                    $command->getDescription(),
                ])->toArray()
            );
        });

        $this->discord->run();
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

        return $this->token = config('discord.token');
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
        ];

        return $this->options = [
            ...config('discord.options', []),
            ...$defaultOptions,
        ];
    }

    /**
     * Get the bot commands.
     */
    public function getCommands(): array
    {
        if ($this->commands) {
            return $this->commands;
        }

        $commands = collect(File::allFiles($this->getCommandPath()))
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

                $command = app()->getNamespace().str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    $folders.$className
                );

                return $command;
            })
            ->merge(config('discord.commands', []))
            ->unique()
            ->filter(fn ($command) => is_subclass_of($command, Command::class) && ! (new ReflectionClass($command))->isAbstract())
            ->all();

        return $this->commands = $commands;
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
     * Get the Discord instance.
     */
    public function getDiscord(): Discord
    {
        return $this->discord;
    }

    /**
     * Get the console instance.
     */
    public function getConsole(): LaravelCommand
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
