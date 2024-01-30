<?php

namespace Laracord\Commands\Contracts;

use Discord\Parts\Channel\Message;

interface Command
{
    /**
     * Handle the command.
     */
    public function handle(Message $message, array $args);
}
