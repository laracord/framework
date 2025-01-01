<?php

namespace Laracord\Commands;

use Discord\Parts\Interactions\Command\Command as DiscordCommand;
use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\Contracts\ContextMenu as ContextMenuContract;

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
            $this->resolveHandler([
                'interaction' => $interaction,
                'target' => $target,
            ]);

            return;
        }

        if ($this->isAdminCommand() && ! $this->isAdmin($interaction->member->user)) {
            $this->handleDenied($interaction);

            return;
        }

        $this->resolveHandler([
            'interaction' => $interaction,
            'target' => $target,
        ]);
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
