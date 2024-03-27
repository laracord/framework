<?php

namespace Laracord;

use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Support\ServiceProvider;
use Laracord\Http\Kernel;

class LaracordServiceProvider extends ServiceProvider
{
    /**
     * The default providers.
     *
     * @var array
     */
    protected $providers = [
        \Illuminate\Encryption\EncryptionServiceProvider::class,
        \Illuminate\Hashing\HashServiceProvider::class,
        \Illuminate\Queue\QueueServiceProvider::class,
        \Illuminate\Routing\RoutingServiceProvider::class,
        \Illuminate\Translation\TranslationServiceProvider::class,
        \Illuminate\Validation\ValidationServiceProvider::class,
        \Illuminate\View\ViewServiceProvider::class,
        \Laracord\Providers\RouteServiceProvider::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/app.php', 'app');
        $this->mergeConfigFrom(__DIR__.'/../config/cache.php', 'cache');
        $this->mergeConfigFrom(__DIR__.'/../config/commands.php', 'commands');
        $this->mergeConfigFrom(__DIR__.'/../config/database.php', 'database');
        $this->mergeConfigFrom(__DIR__.'/../config/discord.php', 'discord');
        $this->mergeConfigFrom(__DIR__.'/../config/filesystems.php', 'filesystems');
        $this->mergeConfigFrom(__DIR__.'/../config/view.php', 'view');

        $paths = [
            'cache' => $this->app['config']->get('cache.stores.file.path'),
            'view' => $this->app['config']->get('view.compiled'),
        ];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }

        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }

        $this->app->singleton(KernelContract::class, Kernel::class);
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
            Console\Commands\ControllerMakeCommand::class,
            Console\Commands\EventMakeCommand::class,
            Console\Commands\KeyGenerateCommand::class,
            Console\Commands\MakeCommand::class,
            Console\Commands\MakeSlashCommand::class,
            Console\Commands\ModelMakeCommand::class,
            Console\Commands\ServiceMakeCommand::class,
            Console\Commands\TokenMakeCommand::class,
        ]);
    }
}
