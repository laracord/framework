<?php

namespace Laracord\Events\Discord;

class GuildBanAdd
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Guild\Ban $ban,
    ) {}
}
