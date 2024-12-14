<?php

namespace Laracord\Events\Discord;

class ThreadMembersUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Thread\Thread $thread,
    ) {}
}
