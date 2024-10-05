<?php

namespace Laracord\Events\Discord;

class AutoModerationRuleDelete
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Guild\AutoModeration\Rule $rule,
    ) {}
}
