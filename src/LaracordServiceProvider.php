<?php

namespace Laracord;

use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Support\ServiceProvider;
use Laracord\Http\Kernel;
use LaravelZero\Framework\Components\Database\Provider as DatabaseProvider;

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
        $this->mergeConfigs();
        $this->createDirectories();

        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }

        $this->registerDatabase();

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

    /**
     * Merge the application configuration.
     */
    protected function mergeConfigs(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/app.php', 'app');
        $this->mergeConfigFrom(__DIR__.'/../config/cache.php', 'cache');
        $this->mergeConfigFrom(__DIR__.'/../config/commands.php', 'commands');
        $this->mergeConfigFrom(__DIR__.'/../config/database.php', 'database');
        $this->mergeConfigFrom(__DIR__.'/../config/discord.php', 'discord');
        $this->mergeConfigFrom(__DIR__.'/../config/filesystems.php', 'filesystems');
        $this->mergeConfigFrom(__DIR__.'/../config/view.php', 'view');
    }

    /**
     * Create the application directories.
     */
    protected function createDirectories(): void
    {
        $paths = [
            'cache' => $this->app['config']->get('cache.stores.file.path'),
            'view' => $this->app['config']->get('view.compiled'),
        ];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }

    /**
     * Register the Database service provider if needed.
     */
    protected function registerDatabase(): void
    {
        if (! (new DatabaseProvider($this->app))->isAvailable()) {
            $this->app->booting(fn () => $this->app->register(DatabaseProvider::class));
        }
    }

    /**
     * Merge the given configuration with the existing configuration.
     *
     * @param  string  $path
     * @param  string  $key
     * @return void
     */
    protected function mergeConfigFrom($path, $key)
    {
        if (! ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached())) {
            $config = $this->app->make('config');

            $config->set($key, array_merge(
                $config->get($key, []), require $path
            ));
        }
    }
}
