<?php

namespace Laracord\Events\Discord;

class ChannelUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Channel\Channel $channel,
        public readonly ?\Discord\Parts\Channel\Channel $oldChannel,
    ) {}
}
