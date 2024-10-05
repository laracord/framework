<?php

namespace Laracord\Events\Discord;

class ThreadListSync
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Helpers\Collection $threads,
    ) {
    }
}
