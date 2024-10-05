<?php

namespace Laracord\Events\Discord;

class ApplicationCommandPermissionsUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Guild\CommandPermissions $commandPermission,
        public readonly ?\Discord\Parts\Guild\CommandPermissions $oldCommandPermission,
    ) {
    }
}
