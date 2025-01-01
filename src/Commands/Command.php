<?php

namespace Laracord\Commands;

use Discord\Parts\Channel\Message;
use Illuminate\Support\Str;
use Laracord\Commands\Contracts\Command as CommandContract;

abstract class Command extends AbstractCommand implements CommandContract
{
    /**
     * The command aliases.
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * The command cooldown.
     *
     * @var int
     */
    protected $cooldown = 0;

    /**
     * The command cooldown message.
     *
     * @var string
     */
    protected $cooldownMessage = '';

    /**
     * The command usage.
     *
     * @var string
     */
    protected $usage = '';

    /**
     * Maybe handle the command.
     */
    public function maybeHandle(Message $message, array $args): void
    {
        if (! $this->canDirectMessage() && ! $message->guild_id) {
            return;
        }

        if ($this->getGuild() && $message->guild_id !== $this->getGuild()) {
            return;
        }

        if (! $this->isAdminCommand()) {
            $this->resolveHandler([
                'message' => $message,
                'args' => $args,
            ]);

            return;
        }

        if ($this->isAdminCommand() && ! $this->isAdmin($message->author)) {
            return;
        }

        $this->resolveHandler([
            'message' => $message,
            'args' => $args,
        ]);
    }

    /**
     * Retrieve the command cooldown.
     *
     * @return int
     */
    public function getCooldown()
    {
        return $this->cooldown;
    }

    /**
     * Retrieve the command cooldown message.
     *
     * @return string
     */
    public function getCooldownMessage()
    {
        return $this->cooldownMessage;
    }

    /**
     * Retrieve the command signature.
     *
     * @return string
     */
    public function getSignature()
    {
        return Str::start($this->getName(), $this->bot()->getPrefix());
    }

    /**
     * Retrieve the command usage.
     *
     * @return string
     */
    public function getUsage()
    {
        return $this->usage;
    }

    /**
     * Retrieve the command aliases.
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }
}
