<?php

namespace Laracord\Events\Discord;

class MessageDeleteBulk
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Helpers\Collection $messages,
    ) {}
}
