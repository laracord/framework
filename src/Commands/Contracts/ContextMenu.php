<?php

namespace Laracord\Commands\Contracts;

use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\User;

interface ContextMenu
{
    /**
     * Handle the context menu interaction.
     */
    public function handle(Interaction $interaction, Message|User|null $target): mixed;
}
