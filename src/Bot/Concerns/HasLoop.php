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
     * Register signal handlers for graceful shutdown.
     */
    protected function registerSignalHandlers(): void
    {
        if (! extension_loaded('pcntl')) {
            $this->logger->warning('The pcntl extension is not loaded. Signal handling is disabled.');

            return;
        }

        $loop = $this->getLoop();

        $loop->addSignal(SIGINT, function () {
            $this->logger->info('Received shutdown signal (SIGINT).');

            $this->shutdown();
        });

        $loop->addSignal(SIGTERM, function () {
            $this->logger->info('Received shutdown signal (SIGTERM).');

            $this->shutdown();
        });
    }

    /**
     * Get the event loop.
     */
    public function getLoop(): LoopInterface
    {
        return $this->app->make(LoopInterface::class);
    }
}
