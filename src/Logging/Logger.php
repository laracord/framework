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
     * System is unusable.
     *
     * @param  mixed[]  $context
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->console->outputComponents()->error($message);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param  mixed[]  $context
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->console->outputComponents()->error($message);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param  mixed[]  $context
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->error($message);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param  mixed[]  $context
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->console->outputComponents()->error($message);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param  mixed[]  $context
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->console->outputComponents()->warn($message);
    }

    /**
     * Normal but significant events.
     *
     * @param  mixed[]  $context
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->info($message);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param  mixed[]  $context
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->console->outputComponents()->info($message);
    }

    /**
     * Detailed debug information.
     *
     * @param  mixed[]  $context
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        if (config('app.env') === 'production') {
            return;
        }

        $this->info($message);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param  mixed  $level
     * @param  mixed[]  $context
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->info($message);
    }
}
