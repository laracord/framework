<?php

namespace Laracord\Events\Discord;

class ClientReady
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
    ) {}
}
