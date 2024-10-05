<?php

namespace Laracord\Events\Discord;

class VoiceServerUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly object $voice,
    ) {
    }
}
