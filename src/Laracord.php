<?php

namespace Laracord;

use Discord\DiscordCommandClient as Discord;
use Discord\WebSockets\Intents;
use Laracord\Logging\Logger;
use LaravelZero\Framework\Commands\Command as LaravelCommand;

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
     * @var \Discord\Discord
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
     * The Discord bot commands.
     */
    protected array $commands = [];

    /**
     * The registered bot commands.
     */
    protected array $registeredCommands = [];

    /**
     * Initialize the Discord Bot.
     *
     * @return void
     */
    public function __construct(LaravelCommand $console)
    {
        $this->console = $console;

        $this->name = config('app.name');
        $this->description = config('discord.description');
        $this->token = config('discord.token');
        $this->prefix = config('discord.prefix');
    }

    /**
     * Make the Bot instance.
     *
     * @return $this
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
        $this->discord = new Discord([
            'token' => $this->token,
            'prefix' => $this->prefix,
            'description' => $this->description,
            'defaultHelpCommand' => false,
            'discordOptions' => [
                'intents' => Intents::getDefaultIntents() | Intents::GUILD_MEMBERS,
                'logger' => Logger::make($this->console),
                'loadAllMembers' => true,
            ],
        ]);

        if (config('discord.help')) {
            $this->commands[] = Commands\HelpCommand::class;
        }

        foreach ($this->commands as $command) {
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

        $commands = count($this->registeredCommands);
        $commands = $commands === 1
            ? "<fg=blue>{$commands}</> command"
            : "<fg=blue>{$commands}</> commands";

        $this->console->outputComponents()->info("Booting <fg=blue>{$this->name}</> with {$commands}");

        $this->discord->run();
    }

    /**
     * Get the command list.
     */
    public function getCommands(): array
    {
        return $this->registeredCommands;
    }

    /**
     * Get the Discord instance.
     *
     * @return \Discord\Discord
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
        return $this->prefix;
    }
}
