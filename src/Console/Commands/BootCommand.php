<?php

namespace Laracord\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;

class BootCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'bot:boot
                            {--no-migrate : Boot without running database migrations}
                            {--bot_name= : The name of the bot}
                            {--bot_token= : The token of the bot}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Boot the Discord bot';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Run migrations unless --no-migrate is specified
        if (! $this->option('no-migrate')) {
            $this->callSilent('migrate', ['--force' => true]);
        }

        // Retrieve bot name and token from options or environment
        $botName = $this->option('bot_name') ?? config('discord.description');
        $botToken = $this->option('bot_token') ?? config('discord.token');

        // Check if bot token is provided
        if (empty($botToken)) {
            $this->error('Bot token is required.');
            return;
        }

        // Set the bot name and token in the environment dynamically
        config(['discord.description' => $botName, 'discord.token' => $botToken]);

        // Create the bot instance using singleton with the bot name and token
        $this->app->singleton('bot', function () {
            $botClass = $this->getClass();
            return $botClass::make($this, config('discord.description'), config('discord.token'));
        });

        // Boot the bot
        $this->app->make('bot')->boot();
    }

    /**
     * Get the bot class.
     */
    protected function getClass(): string
    {
        $class = Str::start($this->app->getNamespace(), '\\') . 'Bot';

        return class_exists($class) ? $class : 'Laracord';
    }
}
