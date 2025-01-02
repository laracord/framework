<?php

namespace Laracord\Events\Discord;

class ThreadMemberUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Thread\Member $threadMember,
    ) {}
}
