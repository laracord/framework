<?php

namespace Laracord\Events\Discord;

class ChannelPinsUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly array $pins,
    ) {
    }
}
