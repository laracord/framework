<?php

namespace Laracord;

use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
        \Illuminate\Cookie\CookieServiceProvider::class,
        \Illuminate\Session\SessionServiceProvider::class,
        \Laracord\Providers\RouteServiceProvider::class,
        \Intonate\TinkerZero\TinkerZeroServiceProvider::class,
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

        $this->registerMacros();
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
            'sessions' => $this->app['config']->get('session.files'),
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
     * Register the macros.
     */
    protected function registerMacros(): void
    {
        Cache::macro('getAsync', fn (string $key) => Laracord::handleAsync(fn () => Cache::get($key)));
        Cache::macro('putAsync', fn (string $key, mixed $value, int $seconds) => Laracord::handleAsync(fn () => Cache::put($key, $value, $seconds)));
        Cache::macro('rememberAsync', fn (string $key, int $seconds, callable $callback) => Laracord::handleAsync(fn () => Cache::remember($key, $seconds, $callback)));
        Cache::macro('rememberForeverAsync', fn (string $key, callable $callback) => Laracord::handleAsync(fn () => Cache::rememberForever($key, $callback)));

        Http::macro('getAsync', fn (string $url, array|string|null $query = []) => Laracord::handleAsync(fn () => Http::get($url, $query)));
        Http::macro('postAsync', fn (string $url, array $data = []) => Laracord::handleAsync(fn () => Http::post($url, $data = [])));

        File::macro('getAsync', fn (string $path) => Laracord::handleAsync(fn () => File::get($path)));
        File::macro('putAsync', fn (string $path, mixed $contents) => Laracord::handleAsync(fn () => File::put($path, $contents)));

        Storage::macro('getAsync', fn (string $path) => Laracord::handleAsync(fn () => Storage::get($path)));
        Storage::macro('putAsync', fn (string $path, mixed $contents) => Laracord::handleAsync(fn () => Storage::put($path, $contents)));

        Date::macro('toDiscord', function ($format = 'R') {
            $format = match ($format) {
                't', 'T', 'd', 'D', 'f', 'F', 'R' => $format,
                default => 'R',
            };

            return "<t:{$this->timestamp}:{$format}>";
        });
    }
}
