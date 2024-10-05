<?php

namespace Laracord\Events\Discord;

class GuildCreate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly object $guild,
    ) {
    }
}
