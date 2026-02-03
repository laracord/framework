<?php

namespace Laracord\Events\Discord;

class MessageUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Channel\Message $message,
        public readonly ?\Discord\Parts\Channel\Message $oldMessage,
    ) {}
}
