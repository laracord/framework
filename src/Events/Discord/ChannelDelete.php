<?php

namespace Laracord\Events\Discord;

class ChannelDelete
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Channel\Channel $channel,
    ) {
    }
}
