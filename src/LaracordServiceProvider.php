<?php

namespace Laracord;

use Illuminate\Support\ServiceProvider;

class LaracordServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cache.php', 'cache');
        $this->mergeConfigFrom(__DIR__.'/../config/commands.php', 'commands');
        $this->mergeConfigFrom(__DIR__.'/../config/database.php', 'database');
        $this->mergeConfigFrom(__DIR__.'/../config/discord.php', 'discord');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->commands([
            Console\Commands\AdminCommand::class,
            Console\Commands\BootCommand::class,
            Console\Commands\ConsoleMakeCommand::class,
            Console\Commands\MakeCommand::class,
            Console\Commands\ServiceMakeCommand::class,
        ]);
    }
}
