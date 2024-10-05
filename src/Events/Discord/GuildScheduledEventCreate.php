<?php

namespace Laracord\Events\Discord;

class GuildScheduledEventCreate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Guild\ScheduledEvent $scheduledEvent,
    ) {
    }
}
