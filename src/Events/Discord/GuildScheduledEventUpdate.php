<?php

namespace Laracord\Events\Discord;

class GuildScheduledEventUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Guild\ScheduledEvent $scheduledEvent,
        public readonly ?\Discord\Parts\Guild\ScheduledEvent $oldScheduledEvent,
    ) {
    }
}
