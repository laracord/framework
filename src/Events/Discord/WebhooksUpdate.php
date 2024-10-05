<?php

namespace Laracord\Events\Discord;

class WebhooksUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly object $guild,
        public readonly object $channel,
    ) {}
}
