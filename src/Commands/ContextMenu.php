<?php

namespace Laracord\Commands;

use Discord\Parts\Interactions\Command\Command as DiscordCommand;
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
     * Handle the context menu interaction.
     *
     * @param  \Discord\Parts\Interactions\Interaction  $interaction
     * @return void
     */
    abstract public function handle($interaction);

    /**
     * Maybe handle the context menu interaction.
     *
     * @param  \Discord\Parts\Interactions\Interaction  $interaction
     * @return void
     */
    public function maybeHandle($interaction)
    {
        if (! $this->isAdminCommand()) {
            $this->handle($interaction);

            return;
        }

        if ($this->isAdminCommand() && ! $this->isAdmin($interaction->member->user)) {
            return $interaction->respondWithMessage(
                $this
                    ->message('You do not have permission to run this command.')
                    ->title('Permission Denied')
                    ->error()
                    ->build(),
                ephemeral: true
            );
        }

        $this->handle($interaction);
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
