<?php

namespace Laracord\Events\Discord;

class GuildScheduledEventUserRemove
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly object $data,
    ) {
    }
}
