<?php

namespace Laracord\Events\Discord;

class GuildMemberUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\User\Member $member,
        public readonly ?\Discord\Parts\User\Member $oldMember,
    ) {
    }
}
