<?php

namespace Laracord\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BootCommand extends Command
{
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
            $this->callSilent('migrate', ['--force' => true]);
        }

        $this->handleCache();

        $this->app->singleton('bot', fn () => $this->getClass()::make($this));

        $this->app->make('bot')->boot();
    }

    /**
     * Handle the cache.
     */
    protected function handleCache(): void
    {
        $cache = config('cache.stores.file.path');

        File::ensureDirectoryExists($cache);
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
