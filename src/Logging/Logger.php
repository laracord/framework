<?php

namespace Laracord\Logging;

use LaravelZero\Framework\Commands\Command;
use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface
{
    /**
     * The console instance.
     *
     * @var \LaravelZero\Framework\Commands\Command
     */
    protected $console;

    /**
     * Initialize the logger.
     *
     * @return void
     */
    public function __construct(Command $console)
    {
        $this->console = $console;
    }

    /**
     * Make a new logger instance.
     */
    public static function make(Command $console): Logger
    {
        return new static($console);
    }

    /**
     * {@inheritdoc}
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->console->outputComponents()->error($message);
    }

    /**
     * {@inheritdoc}
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->console->outputComponents()->error($message);
    }

    /**
     * {@inheritdoc}
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->error($message);
    }

    /**
     * {@inheritdoc}
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->console->outputComponents()->error($message);
    }

    /**
     * {@inheritdoc}
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->console->outputComponents()->warn($message);
    }

    /**
     * {@inheritdoc}
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->info($message);
    }

    /**
     * {@inheritdoc}
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->console->outputComponents()->info($message);
    }

    /**
     * {@inheritdoc}
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        if (config('app.env') === 'production') {
            return;
        }

        $this->info($message);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->info($message);
    }
}
