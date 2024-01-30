<?php

namespace Laracord\Commands\Contracts;

use Discord\Parts\Interactions\Interaction;

interface SlashCommand
{
    /**
     * Handle the slash command.
     */
    public function handle(Interaction $interaction);
}
