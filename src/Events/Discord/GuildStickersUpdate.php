<?php

namespace Laracord\Events\Discord;

class GuildStickersUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Helpers\Collection $stickers,
        public readonly \Discord\Helpers\Collection $oldStickers,
    ) {
    }
}
