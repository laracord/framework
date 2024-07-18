<?php

namespace Laracord\Commands;

use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Command\Command as DiscordCommand;
use Discord\Parts\Permissions\RolePermission;
use Laracord\Commands\Contracts\ContextMenu as ContextMenuContract;

abstract class ContextMenu extends AbstractCommand implements ContextMenuContract
{
    /**
     * The permissions required to use the command.
     *
     * @var array
     */
    protected $permissions = [];

    /**
     * Create a Discord command instance.
     */
    public function create(): DiscordCommand
    {
        $command = CommandBuilder::new()
            ->setName($this->getCleanName())
            ->setType($this->getType())
            ->setDescription($this->getDescription());

        if ($permissions = $this->getPermissions()) {
            $command = $command->setDefaultMemberPermissions($permissions);
        }

        $command = $command->toArray();

        unset($command['description']);
        $command['name'] = $this->getName();

        $command = collect($command)
            ->put('guild_id', $this->getGuild())
            ->filter()
            ->all();

        return new DiscordCommand($this->discord(), $command);
    }

    /**
     * Handle the slash command.
     *
     * @param  \Discord\Parts\Interactions\Interaction  $interaction
     * @return void
     */
    abstract public function handle($interaction);

    /**
     * Maybe handle the slash command.
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
     * Retrieve the command signature.
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->getName();
    }

    /**
     * Retrieve the slash command bitwise permission.
     */
    public function getPermissions(): ?string
    {
        if (! $this->permissions) {
            return null;
        }

        $permissions = collect($this->permissions)
            ->mapWithKeys(fn ($permission) => [$permission => true])
            ->all();

        return (new RolePermission($this->discord(), $permissions))->__toString();
    }

    public function getCleanName()
    {
        // Current Discord-PHP doesn't support context menu names with spaces, we'll work around this for the moment.
        return str_replace(' ', '-', strtolower($this->name));
    }
}
