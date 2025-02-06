<?php

namespace Laracord\Console\Commands;

use Laracord\Laracord;

class BootCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'bot:boot
                            {--token= : The Discord bot token}
                            {--shard-id= : The Discord bot shard ID}
                            {--shard-count= : The Discord bot shard count}
                            {--no-migrate : Boot without running database migrations}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Boot the Discord bot';

    /**
     * Execute the console command.
     */
    public function handle(Laracord $bot): void
    {
        if (! $this->option('no-migrate')) {
            $this->callSilent('migrate', ['--force' => true]);
        }

        if ($this->option('token')) {
            $bot->setToken($this->option('token'));
        }

        if (filled($this->option('shard-id')) && filled($this->option('shard-count'))) {
            $bot->setShard(
                id: $this->option('shard-id'),
                count: $this->option('shard-count')
            );

            $bot->disableHttpServer();
        }

        $bot->boot();
    }
}
