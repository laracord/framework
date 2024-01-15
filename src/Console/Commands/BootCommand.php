<?php

namespace Laracord\Console\Commands;

use Illuminate\Support\Str;
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
    protected $signature = 'bot:boot
                            {--no-migrate : Boot without running database migrations}';

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
        if (! $this->option('no-migrate')) {
            $this->callSilent('migrate');
        }

        $this->getClass()::make($this)->boot();
    }

    /**
     * Get the bot class.
     */
    protected function getClass(): string
    {
        $class = Str::start($this->app->getNamespace(), '\\').'Bot';

        return class_exists($class) ? $class : 'Laracord';
    }
}
