<?php

namespace Laracord\Events\Discord;

class IntegrationUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Guild\Integration $integration,
        public readonly ?\Discord\Parts\Guild\Integration $oldIntegration,
    ) {
    }
}
