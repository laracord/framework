<?php

namespace Laracord\Events\Discord;

class GuildScheduledEventDelete
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Guild\ScheduledEvent $scheduledEvent,
    ) {}
}
