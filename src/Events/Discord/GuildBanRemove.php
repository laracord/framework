<?php

namespace Laracord\Events\Discord;

class GuildBanRemove
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Guild\Ban $ban,
    ) {}
}
