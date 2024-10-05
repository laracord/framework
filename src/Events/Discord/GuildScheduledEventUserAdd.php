<?php

namespace Laracord\Events\Discord;

class GuildScheduledEventUserAdd
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly object $data,
    ) {}
}
