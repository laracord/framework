<?php

namespace Laracord\Events\Discord;

class ClientReconnected
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
    ) {}
}
