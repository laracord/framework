<?php

namespace Laracord\Commands;

class HelpCommand extends Command
{
    /**
     * The Discord command name.
     *
     * @var string
     */
    protected $name = 'help';

    /**
     * The Discord command description.
     *
     * @var string|null
     */
    protected $description = 'View the command help.';

    /**
     * Indicates whether the command should be displayed in the commands list.
     *
     * @var bool
     */
    protected $hidden = true;

    /**
     * The response title.
     *
     * @var string
     */
    protected $title = 'Bot Help';

    /**
     * The response message.
     *
     * @var string
     */
    protected $message = 'Here is a list of all available commands.';

    /**
     * Execute the Discord command.
     *
     * @param  \Discord\Parts\Channel\Message  $message
     * @param  array  $args
     * @return mixed
     */
    public function handle($message, $args)
    {
        $commands = collect($this->getBot()->getRegisteredCommands())->filter(fn ($command) => ! $command->isHidden());

        $fields = [];

        foreach ($commands as $command) {
            $fields[$command->getSyntax()] = $command->getDescription();
        }

        if (count($fields) % 3 !== 0) {
            $fields[' '] = '';
        }

        if (count($fields) % 3 !== 0) {
            $fields['  '] = '';
        }

        return $this->message(
            $message->channel,
            $this->title,
            $this->message,
            $fields
        );
    }
}
