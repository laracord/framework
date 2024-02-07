<?php

namespace Laracord\Logging;

use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface
{
    /**
     * Log messages that should be ignored.
     *
     * @var array
     */
    protected $except = [
        'sending heartbeat',
        'received heartbeat',
        'http not checking',
        'resetting payload count',
    ];

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
        $this->error($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->error($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->error($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->handle($message, $context, 'error');
    }

    /**
     * {@inheritdoc}
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->handle($message, $context, 'warn');
    }

    /**
     * {@inheritdoc}
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->info($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->handle($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        if (app()->environment('production')) {
            return;
        }

        $this->info($message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->info($message, $context);
    }

    /**
     * Handle the log message.
     */
    public function handle(string|\Stringable $message, array $context = [], string $type = 'info'): void
    {
        $type = match ($type) {
            'error' => 'error',
            'warn' => 'warn',
            default => 'info',
        };

        if (Str::of($message)->lower()->contains($this->except)) {
            return;
        }

        $message = ucfirst($message);

        if (method_exists($this->console, 'log')) {
            $this->console->log($message, $type);
        } else {
            $this->console->outputComponents()->{$type}($message);
        }

        $this->handleContext($context, $type);
    }

    /**
     * Handle the log context.
     *
     * @return array
     */
    protected function handleContext(array $context = [], string $type = 'info'): void
    {
        if (! Str::is('error', $type)) {
            return;
        }

        $context = collect($context)->filter();

        if ($context->isEmpty()) {
            return;
        }

        $type = match ($type) {
            'error' => 'red',
            'warn' => 'yellow',
            default => 'blue',
        };

        $context = $context
            ->mapWithKeys(fn ($value, $key) => [Str::is('e', $key) ? 'Error' : $key => $value])
            ->map(fn ($value, $key) => sprintf('<fg=%s>%s</>: %s</>', $type, Str::headline($key), $value));

        $this->console->outputComponents()->bulletList($context->all());
    }
}
