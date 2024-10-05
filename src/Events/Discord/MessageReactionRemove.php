<?php

namespace Laracord\Events\Discord;

class MessageReactionRemove
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\WebSockets\MessageReaction $reaction,
    ) {
    }
}
