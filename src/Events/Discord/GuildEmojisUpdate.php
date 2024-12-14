<?php

namespace Laracord\Events\Discord;

class GuildEmojisUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Helpers\Collection $emojis,
        public readonly \Discord\Helpers\Collection $oldEmojis,
    ) {}
}
