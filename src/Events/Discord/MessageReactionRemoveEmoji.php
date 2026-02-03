<?php

namespace Laracord\Events\Discord;

class MessageReactionRemoveEmoji
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\WebSockets\MessageReaction $reaction,
    ) {}
}
