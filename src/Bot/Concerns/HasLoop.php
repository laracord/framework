<?php

namespace Laracord\Bot\Concerns;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

trait HasLoop
{
    /**
     * The event loop.
     */
    protected ?LoopInterface $loop = null;

    /**
     * Get the event loop.
     */
    public function getLoop(): LoopInterface
    {
        return $this->app->make(LoopInterface::class);
    }
}
