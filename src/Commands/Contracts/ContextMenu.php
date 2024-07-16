<?php

namespace Laracord\Commands\Contracts;

use Discord\Parts\Interactions\Interaction;

interface ContextMenu
{
    /**
     * Handle the context menu.
     */
    public function handle(Interaction $interaction);
}
