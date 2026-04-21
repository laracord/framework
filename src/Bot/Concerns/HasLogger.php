<?php

namespace Laracord\Bot\Concerns;

use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

trait HasLogger
{
    /**
     * The logger instance.
     */
    public ?LogManager $logger = null;

    /**
     * Register the logger.
     */
    protected function registerLogger(): void
    {
        if ($this->logger) {
            return;
        }

        $this->logger = $this->app->make(LoggerInterface::class);
    }

    /**
     * Get the logger instance.
     */
    public function getLogger(): ?LogManager
    {
        return $this->logger;
    }
}
