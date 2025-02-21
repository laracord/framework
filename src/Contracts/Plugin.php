<?php

namespace Laracord\Contracts;

use Laracord\Laracord;

interface Plugin
{
    /**
     * Register the plugin with the bot.
     */
    public function register(Laracord $bot): void;
}
