<?php

namespace Laracord\Events\Discord;

class GuildRoleCreate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Guild\Role $role,
    ) {}
}
