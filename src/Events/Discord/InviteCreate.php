<?php

namespace Laracord\Events\Discord;

class InviteCreate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Channel\Invite $invite,
    ) {}
}
