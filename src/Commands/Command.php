<?php

namespace Laracord\Commands;

use Discord\Parts\Channel\Message;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;
use Laracord\Commands\Contracts\Command as CommandContract;
use Laracord\Commands\Middleware\Context;

abstract class Command extends AbstractCommand implements CommandContract
{
    /**
     * The command aliases.
     *
     * @var array
     */
    protected $aliases = [];

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

        if ($this->isOnCooldown($message->author, $message->guild)) {
            return;
        }

        if (! $this->isAdminCommand()) {
            $this->processMiddleware($message, $args);

            return;
        }

        if ($this->isAdminCommand() && ! $this->isAdmin($message->author)) {
            return;
        }

        $this->processMiddleware($message, $args);
    }

    /**
     * Process the command through its middleware stack.
     */
    protected function processMiddleware(Message $message, array $args): mixed
    {
        $context = new Context(
            source: $message,
            args: $args,
            command: $this
        );

        return (new Pipeline($this->bot()->app))
            ->send($context)
            ->through($this->getMiddleware())
            ->then(fn (Context $context) => $this->resolveHandler([
                'message' => $context->source,
                'args' => $context->args,
            ]));
    }

    /**
     * Retrieve the command signature.
     */
    public function getSignature(): string
    {
        return Str::start($this->getName(), $this->bot()->getPrefix());
    }

    /**
     * Retrieve the command aliases.
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Retrieve the command usage.
     */
    public function getUsage(): string
    {
        return $this->usage;
    }
}
