<?php

namespace Laracord\Events\Discord;

class MessageReactionRemoveAll
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\WebSockets\MessageReaction $reaction,
    ) {}
}
