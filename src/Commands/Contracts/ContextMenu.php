<?php

namespace Laracord\Commands\Contracts;

use Discord\Parts\Interactions\Interaction;

interface ContextMenu
{
    /**
     * Handle the context menu interaction.
     */
    public function handle(Interaction $interaction);
}
