<?php

namespace Laracord\Events\Discord;

class ThreadUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Thread\Thread $thread,
        public readonly ?\Discord\Parts\Thread\Thread $oldThread,
    ) {
    }
}
