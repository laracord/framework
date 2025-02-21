<?php

namespace Laracord\Console\Prompts;

use Laracord\Console\Console;

class HelpPrompt extends Prompt
{
    /**
     * The name of the prompt.
     *
     * @var string
     */
    protected $name = 'help';

    /**
     * The prompt aliases.
     *
     * @var array
     */
    protected $aliases = ['?', 'list'];

    /**
     * The description of the prompt.
     *
     * @var string
     */
    protected $description = 'Show the available commands.';

    /**
     * Handle the prompt.
     */
    public function handle(Console $console): void
    {
        $commands = collect($console->getCommands());

        $console->table(
            ['<fg=blue>Name</>', '<fg=blue>Description</>'],
            $commands->map(fn ($command) => [
                $command->getName(),
                $command->getDescription(),
            ])->all(),
            tableStyle: 'box',
        );
    }
}
