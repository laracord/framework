<?php

namespace Laracord\Commands;

use Discord\Parts\Channel\Message;

class HelpCommand extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'help';

    /**
     * The command description.
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
     * The help title.
     */
    protected static string $title = 'Command Help';

    /**
     * The help message content.
     */
    protected static string $message = 'Here is a list of all available commands.';

    /**
     * Set the help title.
     */
    public static function setTitle(string $title): void
    {
        static::$title = $title;
    }

    /**
     * Set the help message content.
     */
    public static function setMessage(string $message): void
    {
        static::$message = $message;
    }

    /**
     * Handle the command.
     */
    public function handle(Message $message, array $args): void
    {
        $commands = collect($this->bot->getCommands())
            ->filter(fn ($command) => ! $command->isHidden())
            ->filter(fn ($command) => $command->getGuild() ? $message->guild_id === $command->getGuild() : true)
            ->sortBy('name');

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

        $this
            ->message(static::$message)
            ->title(static::$title)
            ->fields($fields)
            ->reply($message);
    }
}
