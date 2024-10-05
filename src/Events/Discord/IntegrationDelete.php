<?php

namespace Laracord\Events\Discord;

class IntegrationDelete
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly object $integration,
    ) {
    }
}
