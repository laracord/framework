<?php

namespace Laracord\Events\Discord;

class GuildRoleDelete
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly object $role,
    ) {}
}
