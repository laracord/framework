<?php

namespace Laracord\Logging;

use Illuminate\Support\Str;
use Laracord\Console\Console;
use NunoMaduro\Collision\Writer;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;
use Whoops\Exception\Inspector;

class Logger implements LoggerInterface
{
    /**
     * Log messages that should be ignored.
     */
    protected array $except = [
        'sending heartbeat',
        'received heartbeat',
        'http not checking',
        'resetting payload count',
    ];

    /**
     * The console instance.
     */
    protected Console $console;

    /**
     * Initialize the logger.
     */
    public function __construct(Console $console)
    {
        $this->console = $console;
    }

    /**
     * Make a new logger instance.
     */
    public static function make(?Console $console = null): static
    {
        return new static($console ?? app(Console::class));
    }

    /**
     * {@inheritdoc}
     */
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->handle($message, $context, LogLevel::EMERGENCY);
    }

    /**
     * {@inheritdoc}
     */
    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->handle($message, $context, LogLevel::ALERT);
    }

    /**
     * {@inheritdoc}
     */
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->handle($message, $context, LogLevel::CRITICAL);
    }

    /**
     * {@inheritdoc}
     */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->handle($message, $context, LogLevel::ERROR);
    }

    /**
     * {@inheritdoc}
     */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->handle($message, $context, LogLevel::WARNING);
    }

    /**
     * {@inheritdoc}
     */
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->info($message, $context, LogLevel::NOTICE);
    }

    /**
     * {@inheritdoc}
     */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->handle($message, $context, LogLevel::INFO);
    }

    /**
     * {@inheritdoc}
     */
    public function debug(string|Stringable $message, array $context = []): void
    {
        if (app()->environment('production')) {
            return;
        }

        $this->handle($message, $context, LogLevel::DEBUG);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->handle($message, $context, $level);
    }

    /**
     * Handle the log message.
     */
    public function handle(string|Stringable $message, array $context = [], string $type = 'info'): void
    {
        $type = match (strtolower($type)) {
            'alert' => LogLevel::ALERT,
            'critical' => LogLevel::CRITICAL,
            'debug' => LogLevel::DEBUG,
            'emergency' => LogLevel::EMERGENCY,
            'error' => LogLevel::ERROR,
            'info' => LogLevel::INFO,
            'warning' => LogLevel::WARNING,
            default => LogLevel::INFO,
        };

        if (Str::of($message)->lower()->contains($this->except)) {
            return;
        }

        if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
            tap(new Writer, fn (Writer $writer) => $writer->write(new Inspector($context['exception'])));

            return;
        }

        $message = ucfirst($message);

        if (method_exists($this->console, 'log')) {
            $this->console->log($type, $message, $context);
        } else {
            $component = match ($type) {
                LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR => 'error',
                LogLevel::WARNING => 'warn',
                default => 'info',
            };

            $this->console->outputComponents()->{$component}($message);
        }
    }
}
