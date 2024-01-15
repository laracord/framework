<?php

namespace Laracord\Commands\Contracts;

use Discord\Parts\Channel\Message;

interface Command
{
    /**
     * Execute the Discord command.
     *
     * @param  \Discord\Parts\Channel\Message  $message
     * @param  array  $args
     * @return void
     */
    public function handle(Message $message, array $args);
}
