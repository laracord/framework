<?php

namespace Laracord\Events\Discord;

class ClientTrace
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly mixed $servers,
    ) {}
}
