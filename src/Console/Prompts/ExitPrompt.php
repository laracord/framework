<?php

namespace Laracord\Console\Prompts;

use Laracord\Laracord;

class ExitPrompt extends Prompt
{
    /**
     * The name of the prompt.
     *
     * @var string
     */
    protected $name = 'exit';

    /**
     * The prompt aliases.
     *
     * @var array
     */
    protected $aliases = ['shutdown', 'disconnect', 'quit', 'stop'];

    /**
     * The description of the prompt.
     *
     * @var string
     */
    protected $description = 'Shut down the bot.';

    /**
     * Handle the prompt.
     */
    public function handle(Laracord $bot): void
    {
        $bot->shutdown();
    }
}
