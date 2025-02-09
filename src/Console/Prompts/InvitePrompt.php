<?php

namespace Laracord\Console\Prompts;

use Laracord\Console\Console;
use Laracord\Laracord;

class InvitePrompt extends Prompt
{
    /**
     * The name of the prompt.
     *
     * @var string
     */
    protected $name = 'invite';

    /**
     * The description of the prompt.
     *
     * @var string
     */
    protected $description = 'Show an invite link for the bot.';

    /**
     * Handle the prompt.
     */
    public function handle(Console $console, Laracord $bot): void
    {
        $bot->showInvite(force: true);
    }
}
