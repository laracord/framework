<?php

namespace Laracord;

use Discord\DiscordCommandClient as Discord;
use Laracord\Console\Commands\Command as ConsoleCommand;
use Laracord\Discord\Message;

trait HasLaracord
{
    /**
     * The bot instance.
     */
    protected ?Laracord $bot = null;

    /**
     * Retrieve the bot instance.
     */
    public function bot(): Laracord
    {
        if ($this->bot) {
            return $this->bot;
        }

        return $this->bot = app('bot');
    }

    /**
     * Retrieve the Discord instance.
     */
    public function discord(): Discord
    {
        return $this->bot()->discord();
    }

    /**
     * Retrieve the console instance.
     */
    public function console(): ConsoleCommand
    {
        return $this->bot()->console();
    }

    /**
     * Build an embed for use in a Discord message.
     *
     * @param  string  $content
     * @return \Laracord\Discord\Message
     */
    public function message($content = '')
    {
        return Message::make($this->bot())
            ->content($content);
    }
}
