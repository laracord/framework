<?php

namespace Laracord\Events\Discord;

class GuildRoleUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Guild\Role $role,
        public readonly ?\Discord\Parts\Guild\Role $oldRole,
    ) {}
}
