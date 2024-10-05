<?php

namespace Laracord\Events\Discord;

class MessageCreate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Channel\Message $message,
    ) {
    }
}
