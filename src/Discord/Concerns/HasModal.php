<?php

namespace Laracord\Discord\Concerns;

use Discord\Parts\Interactions\Interaction;
use Laracord\Discord\Modal;

trait HasModal
{
    /**
     * Retrieve a modal instance.
     */
    public function modal(?string $title = null, ?Interaction $interaction = null): Modal
    {
        return Modal::make($title, $interaction);
    }
}
