<?php

namespace Laracord\Events\Discord;

class TypingStart
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\WebSockets\TypingStart $typing,
    ) {}
}
