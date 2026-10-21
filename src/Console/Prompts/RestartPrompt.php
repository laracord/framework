<?php

namespace Laracord\Console\Prompts;

use Laracord\Laracord;

class RestartPrompt extends Prompt
{
    /**
     * The name of the prompt.
     *
     * @var string
     */
    protected $name = 'restart';

    /**
     * The prompt aliases.
     *
     * @var array
     */
    protected $aliases = ['reboot', 'reconnect'];

    /**
     * The description of the prompt.
     *
     * @var string
     */
    protected $description = 'Restart the bot.';

    /**
     * Handle the prompt.
     */
    public function handle(Laracord $bot): void
    {
        $bot->restart();
    }
}
