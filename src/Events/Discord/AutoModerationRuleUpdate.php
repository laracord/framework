<?php

namespace Laracord\Events\Discord;

class AutoModerationRuleUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Guild\AutoModeration\Rule $rule,
        public readonly ?\Discord\Parts\Guild\AutoModeration\Rule $oldRule,
    ) {
    }
}
