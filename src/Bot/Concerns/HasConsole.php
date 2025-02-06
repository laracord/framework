<?php

namespace Laracord\Bot\Concerns;

use Laracord\Console\Console;
use Laracord\Console\Prompts\Prompt;
use Laracord\Logging\ConsoleHandler;
use Psr\Log\LoggerInterface;

trait HasConsole
{
    /**
     * The console instance.
     */
    public ?Console $console = null;

    /**
     * Determine whether to show the commands on boot.
     */
    protected bool $showCommands = true;

    /**
     * Show the invite link if the bot is not in any guilds.
     */
    protected bool $showInvite = true;

    /**
     * The console prompts.
     */
    public array $prompts = [];

    /**
     * Register the console.
     */
    protected function registerConsole(): void
    {
        if ($this->console) {
            return;
        }

        $this->app->make(LoggerInterface::class)->pushHandler(new ConsoleHandler);

        $this->console = $this->app->make(Console::class);

        foreach ($this->getPrompts() as $prompt) {
            $this->console->addCommand($prompt);
        }
    }

    /**
     * Register a console prompt.
     */
    public function registerPrompt(Prompt|string $prompt): self
    {
        if (is_string($prompt)) {
            $prompt = $prompt::make();
        }

        if (! is_subclass_of($prompt, Prompt::class)) {
            throw new InvalidArgumentException("Class [{$prompt}] is not a valid prompt.");
        }

        $this->prompts[$prompt::class] = $prompt;

        return $this;
    }

    /**
     * Register multiple console prompts.
     */
    public function registerPrompts(array $prompts): self
    {
        foreach ($prompts as $prompt) {
            $this->registerPrompt($prompt);
        }

        return $this;
    }

    /**
     * Print the registered commands to console.
     */
    public function showCommands(): self
    {
        if (! $this->showCommands) {
            return $this;
        }

        $this->console->table(
            ['<fg=blue>Command</>', '<fg=blue>Description</>'],
            collect($this->commands)->map(fn ($command) => [
                $command->getSignature(),
                $command->getDescription(),
            ])->all()
        );

        return $this;
    }

    /**
     * Show the invite link if the bot is not in any guilds.
     */
    public function showInvite(bool $force = false): self
    {
        if (! $force && (! $this->showInvite || $this->discord->guilds->count() > 0)) {
            return $this;
        }

        if (! $force) {
            $this->logger->warning("{$this->getName()} is currently not in any guilds.");
        }

        $query = Arr::query([
            'client_id' => $this->discord->id,
            'permissions' => 281600,
            'scope' => 'bot applications.commands',
        ]);

        $invite = "https://discord.com/oauth2/authorize?{$query}";

        $this->logger->info("You can <fg=blue>invite {$this->getName()}</> using the following link: <fg=blue>{$invite}</>");

        return $this;
    }

    /**
     * Get the registered prompts.
     */
    public function getPrompts(): array
    {
        return $this->prompts;
    }

    /**
     * Get the console instance.
     */
    public function console(): ?Console
    {
        return $this->console;
    }
}
