<?php

namespace Laracord;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Support\AggregateServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laracord\Console\Commands;
use Laracord\Console\Console;
use Laracord\Console\Prompts;
use Laracord\Discord\Message;
use Laracord\Http\Kernel;
use LaravelZero\Framework\Components\Database\Provider as DatabaseProvider;
use LaravelZero\Framework\Components\Log\Provider as LogProvider;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Stream\CompositeStream;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Finder\Finder;

abstract class LaracordServiceProvider extends AggregateServiceProvider
{
    /**
     * The provider class names.
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
        \Laracord\Http\Providers\RouteServiceProvider::class,
        \Intonate\TinkerZero\TinkerZeroServiceProvider::class,
    ];

    abstract public function bot(Laracord $bot): Laracord;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigs();
        $this->createDirectories();

        parent::register();

        $this->registerDatabase();
        $this->registerLoop();
        $this->registerConsole();
        $this->registerLogger();

        $this->app->singleton(KernelContract::class, Kernel::class);

        $this->app->singleton(Laracord::class, fn () => tap(Laracord::make($this->app), function (Laracord $bot) {
            $this
                ->registerDefaultComponents($bot)
                ->registerDefaultPrompts($bot);

            $this->app->singleton(Message::class, fn () => Message::make($bot));

            return $this->bot($bot);
        }));

        $this->app->alias(Laracord::class, 'bot');
        $this->app->alias(Message::class, 'bot.message');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->commands([
            Commands\AdminCommand::class,
            Commands\BootCommand::class,
            Commands\ConsoleMakeCommand::class,
            Commands\ControllerMakeCommand::class,
            Commands\EventMakeCommand::class,
            Commands\KeyGenerateCommand::class,
            Commands\MakeCommand::class,
            Commands\MakeCommandMiddlewareCommand::class,
            Commands\MakeSlashCommand::class,
            Commands\MakeMenuCommand::class,
            Commands\ModelMakeCommand::class,
            Commands\ServiceMakeCommand::class,
            Commands\TokenMakeCommand::class,
        ]);

        $this->registerMacros();
    }

    /**
     * Register the event loop.
     */
    protected function registerLoop(): void
    {
        $this->app->singleton(LoopInterface::class, fn () => Loop::get());
        $this->app->alias(LoopInterface::class, 'bot.loop');
    }

    /**
     * Register the console.
     */
    protected function registerConsole(): void
    {
        $this->app->singleton(Console::class, function () {
            $loop = $this->app->make(LoopInterface::class);

            $console = new Console(
                stdio: new CompositeStream(
                    new ReadableResourceStream(STDIN, $loop),
                    new WritableResourceStream(STDOUT, $loop),
                ),
                laravel: $this->app,
                output: new ConsoleOutput,
                input: new StringInput(''),
            );

            if ($console->hasColorSupport()) {
                $console->getOutput()->setDecorated(true);
            }

            // foreach ($this->app->make(ConsoleKernel::class)->all() as $command) {
            //     if ($command instanceof Command) {
            //         $console->addCommand($command);
            //     }
            // }

            $this->app->instance('bot.console', $console);

            return $console;
        });
    }

    /**
     * Register the logger.
     */
    protected function registerLogger(): void
    {
        $this->app->booting(fn () => $this->app->register(LogProvider::class));
    }

    /**
     * Register the default components.
     */
    protected function registerDefaultComponents(Laracord $bot): self
    {
        $bot
            ->discoverCommands(in: app_path('Commands'), for: 'App\\Commands')
            ->discoverSlashCommands(in: app_path('SlashCommands'), for: 'App\\SlashCommands')
            ->discoverContextMenus(in: app_path('Menus'), for: 'App\\Menus')
            ->discoverEvents(in: app_path('Events'), for: 'App\\Events')
            ->discoverServices(in: app_path('Services'), for: 'App\\Services');

        return $this;
    }

    /**
     * Register the default console prompts.
     */
    protected function registerDefaultPrompts(Laracord $bot): self
    {
        $bot->registerPrompts([
            Prompts\ExitPrompt::class,
            Prompts\HelpPrompt::class,
            Prompts\InvitePrompt::class,
            Prompts\RestartPrompt::class,
            Prompts\StatusPrompt::class,
            Prompts\ClearPrompt::class,
        ]);

        return $this;
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

        Date::macro('toDiscord', function (string $format = 'R') {
            $format = match ($format) {
                't', 'T', 'd', 'D', 'f', 'F', 'R' => $format,
                default => 'R',
            };

            return "<t:{$this->timestamp}:{$format}>";
        });
    }
}
