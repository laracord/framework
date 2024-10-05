<?php

namespace Laracord\Events\Discord;

class InviteDelete
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly object $invite,
    ) {
    }
}
