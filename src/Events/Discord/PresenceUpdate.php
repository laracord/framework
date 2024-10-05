<?php

namespace Laracord\Events\Discord;

class PresenceUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\WebSockets\Events\PresenceUpdate $presence,
    ) {
    }
}
