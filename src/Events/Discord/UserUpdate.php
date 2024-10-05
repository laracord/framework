<?php

namespace Laracord\Events\Discord;

class UserUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\User\User $user,
        public readonly ?\Discord\Parts\User\User $oldUser,
    ) {
    }
}
