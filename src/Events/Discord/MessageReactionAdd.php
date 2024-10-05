<?php

namespace Laracord\Events\Discord;

class MessageReactionAdd
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\WebSockets\MessageReaction $reaction,
    ) {
    }
}
