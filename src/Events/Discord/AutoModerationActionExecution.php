<?php

namespace Laracord\Events\Discord;

class AutoModerationActionExecution
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\WebSockets\AutoModerationActionExecution $actionExecution,
    ) {}
}
