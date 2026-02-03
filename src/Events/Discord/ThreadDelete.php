<?php

namespace Laracord\Events\Discord;

class ThreadDelete
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly object $thread,
    ) {}
}
