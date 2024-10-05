<?php

namespace Laracord\Events\Discord;

class VoiceStateUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\WebSockets\VoiceStateUpdate $state,
        public readonly ?\Discord\Parts\WebSockets\VoiceStateUpdate $oldState,
    ) {
    }
}
