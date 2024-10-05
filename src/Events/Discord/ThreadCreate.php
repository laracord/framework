<?php

namespace Laracord\Events\Discord;

class ThreadCreate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Thread\Thread $thread,
    ) {}
}
