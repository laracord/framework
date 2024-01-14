<?php

namespace Laracord\Console\Concerns;

use Laracord\Console\Components\Log;

trait WithLog
{
    /**
     * Send a message to the console.
     *
     * @return void
     */
    public function log(string $message, string $type = 'info')
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
}
