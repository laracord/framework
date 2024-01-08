<?php

namespace Laracord\Commands\Contracts;

use Discord\Parts\Channel\Message;

interface Command
{
    /**
     * Execute the Discord command.
     *
     * @return mixed
     */
    public function handle(Message $message, array $args);
}
