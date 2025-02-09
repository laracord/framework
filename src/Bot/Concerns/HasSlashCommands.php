<?php

namespace Laracord\Bot\Concerns;

use InvalidArgumentException;
use Laracord\Commands\SlashCommand;

trait HasSlashCommands
{
    /**
     * The registered slash commands.
     */
    protected array $slashCommands = [];

    /**
     * Register a slash command.
     */
    public function registerSlashCommand(SlashCommand|string $command): self
    {
        if (is_string($command)) {
            $command = $command::make();
        }

        if (! is_subclass_of($command, SlashCommand::class)) {
            $class = $command::class;

            throw new InvalidArgumentException("Class [{$class}] is not a valid slash command.");
        }

        $this->slashCommands[$command::class] = $command;

        return $this;
    }

    /**
     * Register multiple slash commands.
     */
    public function registerSlashCommands(array $commands): self
    {
        foreach ($commands as $command) {
            $this->registerSlashCommand($command);
        }

        return $this;
    }

    /**
     * Discover slash commands in a path.
     */
    public function discoverSlashCommands(string $in, string $for): self
    {
        foreach ($this->discover(SlashCommand::class, $in, $for) as $command) {
            $this->registerSlashCommand($command);
        }

        return $this;
    }

    /**
     * Get a registered slash command by name.
     */
    public function getSlashCommand(string $name): ?SlashCommand
    {
        return $this->slashCommands[$name] ?? collect($this->slashCommands)->first(fn (SlashCommand $command): bool => $command->getName() === $name);
    }

    /**
     * Get the registered slash commands.
     */
    public function getSlashCommands(): array
    {
        return $this->slashCommands;
    }
}
