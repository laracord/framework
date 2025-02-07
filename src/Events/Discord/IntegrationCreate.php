<?php

namespace Laracord\Events\Discord;

class IntegrationCreate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Guild\Integration $integration,
    ) {}
}
