<?php

namespace Laracord\Console\Concerns;

use Laracord\Console\Components\Log;

trait WithLog
{
    /**
     * Send a message to the console.
     */
    public function log(string $message, string $type = 'info'): void
    {
        $message = trim($message);

        if (empty($message)) {
            return;
        }

        $color = match ($type) {
            'error' => 'red',
            'warn' => 'yellow',
            default => 'blue',
        };

        $timestamp = config('discord.timestamp');

        $config = [
            'bgColor' => $color,
            'fgColor' => 'white',
            'title' => $type,
            'timestamp' => $timestamp ? now()->format($timestamp) : null,
        ];

        with(new Log($this->getOutput()))->render($config, $message);
    }

    /**
     * Send a warning log to console.
     *
     * @param  string  $string
     * @param  string|null  $verbosity
     * @return void
     */
    public function warn($string, $verbosity = null)
    {
        return $this->log($string, 'warn');
    }

    /**
     * Send an error log to console.
     *
     * @param  string  $string
     * @param  string|null  $verbosity
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        return $this->log($string, 'error');
    }
}
