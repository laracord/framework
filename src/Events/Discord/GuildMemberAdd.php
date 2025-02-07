<?php

namespace Laracord\Events\Discord;

class GuildMemberAdd
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\User\Member $member,
    ) {}
}
