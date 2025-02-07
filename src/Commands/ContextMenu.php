<?php

namespace Laracord\Commands;

use Discord\Parts\Interactions\Command\Command as DiscordCommand;
use Discord\Parts\Interactions\Interaction;
use Illuminate\Pipeline\Pipeline;
use Laracord\Commands\Contracts\ContextMenu as ContextMenuContract;
use Laracord\Commands\Middleware\Context;

abstract class ContextMenu extends ApplicationCommand implements ContextMenuContract
{
    /**
     * The context menu type.
     */
    protected string|int $type = 'message';

    /**
     * Create a Discord command instance.
     */
    public function create(): DiscordCommand
    {
        $menu = collect([
            'name' => $this->getName(),
            'type' => $this->getType(),
            'guild_id' => $this->getGuild(),
            'default_member_permissions' => $this->getPermissions(),
            'default_permission' => true,
            'dm_permission' => $this->canDirectMessage(),
            'nsfw' => $this->isNsfw(),
        ])->reject(fn ($value) => blank($value));

        return new DiscordCommand($this->discord(), $menu->all());
    }

    /**
     * Process the command through its middleware stack.
     */
    protected function processMiddleware(Interaction $interaction, mixed $target = null): mixed
    {
        $context = new Context(
            source: $interaction,
            target: $target,
            command: $this,
        );

        return (new Pipeline($this->bot()->app))
            ->send($context)
            ->through($this->getMiddleware())
            ->then(fn (Context $context) => $this->resolveHandler([
                'interaction' => $context->source,
                'target' => $context->target,
            ]));
    }

    /**
     * Maybe handle the context menu interaction.
     */
    public function maybeHandle(Interaction $interaction): void
    {
        $target = match ($this->getType()) {
            DiscordCommand::USER => $interaction->data->resolved->users?->first(),
            DiscordCommand::MESSAGE => $interaction->data->resolved->messages?->first(),
            default => null,
        };

        if (! $this->isAdminCommand()) {
            $this->processMiddleware($interaction, $target);

            return;
        }

        if ($this->isAdminCommand() && ! $this->isAdmin($interaction->member->user)) {
            $this->handleDenied($interaction);

            return;
        }

        $this->processMiddleware($interaction, $target);
    }

    /**
     * Get the context menu type.
     */
    public function getType(): int
    {
        return match ($this->type) {
            'user' => DiscordCommand::USER,
            DiscordCommand::USER => DiscordCommand::USER,
            default => DiscordCommand::MESSAGE,
        };
    }
}
