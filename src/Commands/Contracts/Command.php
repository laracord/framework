<?php

namespace Laracord\Commands\Contracts;

use Discord\Parts\Channel\Message;

interface Command
{
    /**
     * Execute the Discord command.
     */
    public function handle(Message $message, array $args);
}
