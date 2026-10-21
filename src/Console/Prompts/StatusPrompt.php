<?php

namespace Laracord\Console\Prompts;

use Illuminate\Support\Str;
use Laracord\Console\Console;
use Laracord\Laracord;

class StatusPrompt extends Prompt
{
    /**
     * The name of the prompt.
     *
     * @var string
     */
    protected $name = 'status';

    /**
     * The description of the prompt.
     *
     * @var string
     */
    protected $description = 'Show the bot status.';

    /**
     * Handle the prompt.
     */
    public function handle(Console $console, Laracord $bot): void
    {
        $uptime = $bot->getUptime()->diffForHumans(null, true);

        $status = $bot->getStatus()->merge([
            'user' => $bot->discord->users->count(),
            'guild' => $bot->discord->guilds->count(),
        ])->mapWithKeys(fn ($count, $type) => [Str::plural($type, $count) => $count]);

        $status = $status
            ->prepend($uptime, 'uptime')
            ->prepend("{$bot->discord->username} ({$bot->discord->id})", 'bot')
            ->map(fn ($count, $type) => Str::of($type)->title()->finish(": <fg=blue>{$count}</>")->toString());

        $console->outputComponents()->bulletList($status->all());
    }
}
