<?php

namespace Laracord\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class ConsoleHandler extends AbstractProcessingHandler
{
    /**
     * The logger instance.
     */
    public readonly Logger $logger;

    /**
     * Create a new console handler instance.
     */
    public function __construct($level = Level::Debug, bool $bubble = true, ?Logger $logger = null)
    {
        parent::__construct($level, $bubble);

        $this->logger = $logger ?? Logger::make();
    }

    /**
     * {@inheritdoc}
     */
    protected function write(LogRecord $record): void
    {
        $this->logger->handle($record->message, $record->context, $record->level->getName());
    }
}
