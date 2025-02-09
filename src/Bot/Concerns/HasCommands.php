<?php

namespace Laracord\Bot\Concerns;

use Discord\Parts\Channel\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laracord\Bot\Hook;
use Laracord\Commands\Command;

trait HasCommands
{
    /**
     * The registered commands.
     */
    protected array $commands = [];

    /**
     * The command map.
     */
    protected array $commandMap = [];

    /**
     * The Discord bot command prefix.
     */
    protected ?Collection $prefixes = null;

    /**
     * Retrieve the prefixes.
     */
    public function getPrefixes(): Collection
    {
        if ($this->prefixes) {
            return $this->prefixes;
        }

        $prefixes = collect(config('discord.prefix', '!'))
            ->map(fn ($prefix) => Str::of($prefix)->replace(['@mention', '@self'], (string) $this->discord->user)->trim()->toString())
            ->reject(fn ($prefix) => Str::startsWith($prefix, '/'))
            ->filter();

        if ($prefixes->isEmpty()) {
            throw new Exception('You must provide a valid command prefix.');
        }

        return $this->prefixes = $prefixes;
    }

    /**
     * Retrieve the primary prefix.
     */
    public function getPrefix(): string
    {
        return $this->getPrefixes()->first();
    }

    /**
     * Boot the chat commands.
     */
    protected function bootCommands(): self
    {
        $this->handleCommands();

        $this->callHook(Hook::AFTER_COMMANDS_REGISTERED);

        return $this;
    }

    /**
     * Handle the chat commands.
     */
    protected function handleCommands(): void
    {
        $this->discord->on('message', function (Message $message) {
            if ($message->author->id === $this->discord->id) {
                return;
            }

            $prefix = $this->getPrefixes()->first(fn ($prefix) => Str::startsWith($message->content, $prefix));

            if (! $prefix) {
                return;
            }

            $parts = Str::of($message->content)
                ->after($prefix)
                ->trim()
                ->explode(' ');

            $command = $parts->shift();

            if (! $command) {
                return;
            }

            $command = $this->getCommand($command);

            if (! $command) {
                return;
            }

            rescue(fn () => $command->maybeHandle($message, $parts->all()));
        });
    }

    /**
     * Register a command.
     */
    public function registerCommand(Command|string $command): self
    {
        if (is_string($command)) {
            $command = $command::make();
        }

        if (! is_subclass_of($command, Command::class)) {
            throw new InvalidArgumentException("Class [{$command}] is not a valid command.");
        }

        if (! $command->isEnabled()) {
            return $this;
        }

        $this->commands[$command::class] = $command;

        $this->commandMap[$command->getName()] = $command::class;

        foreach ($command->getAliases() as $alias) {
            $this->commandMap[$alias] = $command::class;
        }

        $this->registerInteractions($command->getName(), $command->interactions());

        return $this;
    }

    /**
     * Register multiple commands.
     */
    public function registerCommands(array $commands): self
    {
        foreach ($commands as $command) {
            $this->registerCommand($command);
        }

        return $this;
    }

    /**
     * Discover commands in a path.
     */
    public function discoverCommands(string $in, string $for): self
    {
        foreach ($this->discover(Command::class, $in, $for) as $command) {
            $this->registerCommand($command);
        }

        return $this;
    }

    /**
     * Get the registered commands.
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get a registered command by name.
     */
    public function getCommand(string $name): ?Command
    {
        $command = $this->commandMap[$name] ?? null;

        if (! $command) {
            return null;
        }

        return $this->commands[$command] ?? null;
    }
}
