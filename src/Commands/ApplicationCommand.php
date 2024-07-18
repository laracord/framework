<?php

namespace Laracord\Commands;

abstract class ApplicationCommand extends AbstractCommand
{
    /**
     * The permissions required to use the command.
     *
     * @var array
     */
    protected $permissions = [];

    /**
     * Determine if the command is not safe for work.
     */
    protected bool $nsfw = false;

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

    /**
     * Determine if the command is not safe for work.
     */
    public function isNsfw(): bool
    {
        return $this->nsfw;
    }
}
