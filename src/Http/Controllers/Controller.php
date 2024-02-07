<?php

namespace Laracord\Http\Controllers;

use Discord\DiscordCommandClient as Discord;
use Illuminate\Routing\Controller as BaseController;
use Laracord\Console\Commands\Command as ConsoleCommand;
use Laracord\Laracord;

class Controller extends BaseController
{
    /**
     * The bot instance.
     */
    protected ?Laracord $bot;

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
}
