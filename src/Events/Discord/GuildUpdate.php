<?php

namespace Laracord\Events\Discord;

class GuildUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Guild\Guild $guild,
        public readonly ?\Discord\Parts\Guild\Guild $oldGuild,
    ) {}
}
