<?php

namespace Laracord\Console\Concerns;

use DateTimeInterface;
use Laracord\Console\Components\Log;
use Psr\Log\LogLevel;
use Stringable;

trait WithLog
{
    /**
     * The output style implementation.
     *
     * @var \Illuminate\Console\OutputStyle
     */
    protected $output;

    /**
     * The log level colors.
     */
    protected array $colors = [
        LogLevel::EMERGENCY => 'red',
        LogLevel::ALERT => 'red',
        LogLevel::CRITICAL => 'red',
        LogLevel::ERROR => 'red',
        LogLevel::WARNING => 'yellow',
        LogLevel::NOTICE => 'cyan',
        LogLevel::INFO => 'blue',
        LogLevel::DEBUG => 'green',
    ];

    /**
     * Render a log message.
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $message = trim($message);

        $timestamp = config('discord.timestamp');

        $config = [
            'bgColor' => $this->color($level),
            'fgColor' => 'white',
            'title' => $level,
            'timestamp' => $timestamp ? now()->format($timestamp) : null,
        ];

        with(new Log($this->output))->render($config, $this->interpolate($level, $message, $context));
    }

    /**
     * Interpolate the message.
     */
    private function interpolate(string $level, string $message, array $context): string
    {
        $color = $this->color($level);

        $replacements = [];

        foreach ($context as $key => $val) {
            $replacements["{{$key}}"] = $this->colorize($color, match (true) {
                $val === null, is_scalar($val), $val instanceof Stringable => "{$val}",
                $val instanceof DateTimeInterface => $val->format(DateTimeInterface::RFC3339),
                is_object($val) => '[object '.$val::class.']',
                default => '['.gettype($val).']',
            });
        }

        return strtr($message, $replacements);
    }

    /**
     * Retrieve the color for the log level.
     */
    private function color(string $level): string
    {
        return $this->colors[$level] ?? $this->colors[LogLevel::INFO];
    }

    /**
     * Colorize the message.
     */
    private function colorize(string $color, string $message)
    {
        return "<fg={$color}>{$message}</>";
    }
}
