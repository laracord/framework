<?php

namespace Laracord\Commands\Middleware;

use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\Contracts\Command;
use Laracord\Commands\Contracts\ContextMenu;
use Laracord\Commands\Contracts\SlashCommand;

class Context
{
    /**
     * Create a new context instance.
     */
    public function __construct(
        public Message|Interaction $source,
        public Command|SlashCommand|ContextMenu|null $command = null,
        public array $args = [],
        public mixed $target = null,
        public array $options = []
    ) {}

    /**
     * Determine if the context is from a message command.
     */
    public function isMessage(): bool
    {
        return $this->source instanceof Message;
    }

    /**
     * Determine if the context is from an interaction.
     */
    public function isInteraction(): bool
    {
        return $this->source instanceof Interaction;
    }

    /**
     * Determine if the command is a slash command.
     */
    public function isSlashCommand(): bool
    {
        return $this->command instanceof SlashCommand;
    }

    /**
     * Determine if the command is a context menu.
     */
    public function isContextMenu(): bool
    {
        return $this->command instanceof ContextMenu;
    }

    /**
     * Determine if the command is a message command.
     */
    public function isCommand(): bool
    {
        return $this->command instanceof Command;
    }

    /**
     * Determine if this is a raw interaction (no command).
     */
    public function isRawInteraction(): bool
    {
        return $this->isInteraction() && $this->command === null;
    }

    /**
     * Get the user from the context.
     */
    public function getUser()
    {
        if ($this->isMessage()) {
            return $this->source->author;
        }

        return $this->source->user ?? $this->source->member?->user;
    }

    /**
     * Get the guild ID from the context.
     */
    public function getGuildId(): ?string
    {
        return $this->source->guild_id;
    }
}
