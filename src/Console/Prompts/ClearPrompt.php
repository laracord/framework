<?php

namespace Laracord\Console\Prompts;

use Laracord\Console\Console;

class ClearPrompt extends Prompt
{
    /**
     * The name of the prompt.
     *
     * @var string
     */
    protected $name = 'clear';

    /**
     * The prompt aliases.
     *
     * @var array
     */
    protected $aliases = ['clear', 'cls'];

    /**
     * The description of the prompt.
     *
     * @var string
     */
    protected $description = 'Clear the console.';

    /**
     * Handle the prompt.
     */
    public function handle(Console $console): void
    {
        $console->line("\033[2J\033[3J\033[H");
    }
}
