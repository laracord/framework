<?php

namespace Laracord\Commands;

use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;

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
    protected static string $message = 'Showing a list of %s available command(s):';

    /**
     * The maximum commands per page.
     */
    protected static int $perPage = 12;

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
     * Set the maximum commands per page.
     */
    public static function setPerPage(int $perPage): void
    {
        static::$perPage = max($perPage, 25) ?: static::$perPage;
    }

    /**
     * Handle the command.
     */
    public function handle(Message $message, array $args): void
    {
        $this->show($message, $args[0] ?? 1);
    }

    /**
     * Show the help command.
     */
    public function show(Message|Interaction $context, int $page = 1): void
    {
        $commands = collect($this->bot->getCommands())
            ->filter(fn ($command) => ! $command->isHidden())
            ->filter(fn ($command) => $command->getGuild() ? $context->guild_id === $command->getGuild() : true)
            ->sortBy('name');

        $page = max(1, $page);

        $items = $commands->forPage($page, static::$perPage);

        $fields = [];

        foreach ($items as $item) {
            $fields[$item->getSyntax()] = $item->getDescription();
        }

        if (count($fields) % 3 !== 0) {
            $fields[' '] = '';
        }

        if (count($fields) % 3 !== 0) {
            $fields['  '] = '';
        }

        $pages = ceil($commands->count() / static::$perPage);
        $previous = max(1, $page - 1);
        $next = min($pages, $page + 1);

        $message = sprintf(static::$message, $commands->count());

        $this
            ->message($message)
            ->title(static::$title)
            ->fields($fields)
            ->button('←', route: "show:{$previous}", style: 'secondary', disabled: $page <= 1, hidden: $pages === 1)
            ->button('→', route: "show:{$next}", style: 'secondary', disabled: $page >= $pages, hidden: $pages === 1)
            ->footerText("Page {$page} of {$pages}")
            ->editOrReply($context);
    }

    /**
     * The command interaction routes.
     */
    public function interactions(): array
    {
        return [
            'show:{page}' => fn (Interaction $interaction, string $page) => $this->show($interaction, (int) $page),
        ];
    }
}
