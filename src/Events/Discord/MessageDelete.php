<?php

namespace Laracord\Events\Discord;

class MessageDelete
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly object $message,
    ) {}
}
