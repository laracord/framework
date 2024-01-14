<?php

namespace Laracord\Console\Commands;

use Laracord\Console\Concerns\WithLog;
use LaravelZero\Framework\Commands\Command;

class BootCommand extends Command
{
    use WithLog;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'bot:boot';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Boot the Discord bot.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        (class_exists('\App\Bot') ? '\App\Bot' : 'Laracord')::make($this)->boot();
    }
}
