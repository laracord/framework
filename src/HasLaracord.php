<?php

namespace Laracord;

use Discord\Discord;
use Laracord\Console\Console;
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
    public function console(): Console
    {
        return $this->bot()->console();
    }

    /**
     * Retrieve the logger instance.
     */
    public function logger(): LogManager
    {
        return $this->bot()->getLogger();
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
