<?php

namespace Laracord\Events\Discord;

class GuildDelete
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly object $guild,
        public readonly bool $unavailable,
    ) {
    }
}