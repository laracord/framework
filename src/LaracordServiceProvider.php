<?php

namespace Laracord;

use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Support\ServiceProvider;
use Laracord\Http\Kernel;
use LaravelZero\Framework\Components\Database\Provider as DatabaseProvider;
use Symfony\Component\Finder\Finder;

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
     * Retrieve configuration files from the specified path.
     */
    protected function getConfigs(string $path): array
    {
        $configs = [];

        foreach (Finder::create()->files()->name('*.php')->in($path) as $file) {
            $configs[basename($file->getPathname(), '.php')] = require $file->getPathname();
        }

        return $configs;
    }

    /**
     * Merge the application configuration.
     */
    protected function mergeConfigs(): void
    {
        $base = $this->getConfigs(__DIR__.'/../config');
        $configs = $this->getConfigs($this->app->configPath());

        foreach ($base as $key => $value) {
            $this->app['config']->set($key, array_merge($value, $configs[$key] ?? []));
        }
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
}
