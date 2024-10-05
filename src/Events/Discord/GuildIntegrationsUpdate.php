<?php

namespace Laracord\Events\Discord;

class GuildIntegrationsUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly object $guild,
    ) {}
}
