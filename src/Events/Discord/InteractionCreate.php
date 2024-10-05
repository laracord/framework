<?php

namespace Laracord\Events\Discord;

class InteractionCreate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Interactions\Interaction $interaction,
    ) {}
}
