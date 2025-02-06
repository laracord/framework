<?php

namespace Laracord;

use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laracord\Bot\Concerns;
use Throwable;

use function React\Promise\set_rejection_handler;

class Laracord
{
    use Concerns\HasApplicationCommands,
        Concerns\HasCommandMiddleware,
        Concerns\HasCommands,
        Concerns\HasComponents,
        Concerns\HasConsole,
        Concerns\HasContextMenus,
        Concerns\HasDiscord,
        Concerns\HasEvents,
        Concerns\HasHooks,
        Concerns\HasHttpServer,
        Concerns\HasInteractions,
        Concerns\HasLogger,
        Concerns\HasLoop,
        Concerns\HasPlugins,
        Concerns\HasServices,
        Concerns\HasSlashCommands;

    /**
     * The boot state.
     */
    protected bool $booted = false;

    /**
     * Initialize the Laracord instance.
     */
    public function __construct(public Application $app)
    {
        set_rejection_handler(fn (Throwable $e) => report($e));
    }

    /**
     * Make a new Laracord instance.
     */
    public static function make(Application $app): self
    {
        return new static($app);
    }

    /**
     * Boot the bot.
     */
    public function boot(): void
    {
        if ($this->isBooted()) {
            return;
        }

        $this->registerConsole();
        $this->registerLogger();

        rescue(fn () => $this->handleBoot());

        $this->booted = true;
    }

    /**
     * Handle the boot process.
     */
    protected function handleBoot(): void
    {
        $this->registerDiscord();

        $this->callHook('beforeBoot');

        $this->discord->on('init', function () {
            $this
                ->bootApplicationCommands()
                ->bootCommands()
                ->bootEvents()
                ->bootServices()
                ->bootHttpServer()
                ->handleInteractions();

            $this->callHook('afterBoot');

            $this->getLoop()->addTimer(1, function () {
                $status = $this
                    ->getStatus()
                    ->map(fn ($count, $type) => "<fg=blue>{$count}</> {$type}")
                    ->implode(', ');

                $status = Str::replaceLast(', ', ', and ', $status);

                $this->logger->info("Successfully booted <fg=blue>{$this->getName()}</> with {$status}.");

                $this
                    ->showCommands()
                    ->showInvite();

                $this->console->newLine()->showPrompt();
            });
        });

        $this->discord->run();
    }

    /**
     * Shutdown the bot.
     */
    public function shutdown(int $code = 0): void
    {
        $this->logger->info("Shutting down <fg=blue>{$this->getName()}</>.");

        $this->httpServer()->shutdown();
        $this->discord()->close();

        exit($code);
    }

    /**
     * Restart the bot.
     */
    public function restart(): void
    {
        $this->logger->info("<fg=blue>{$this->getName()}</> is restarting.");

        $this->discord->close(closeLoop: false);

        $this->discord = null;

        $this->handleBoot();
    }

    /**
     * Retrieve the bot status collection.
     */
    public function getStatus(): Collection
    {
        return collect([
            'command' => count($this->commands),
            'slash command' => count($this->slashCommands),
            'menu' => count($this->contextMenus),
            'event' => count($this->events),
            'service' => count($this->services),
            'interaction' => count($this->interactions),
            'route' => count(Route::getRoutes()->getRoutes()),
        ])->filter()->mapWithKeys(fn ($count, $type) => [Str::plural($type, $count) => $count]);
    }

    /**
     * Retrieve the bot uptime.
     */
    public function getUptime(): Carbon
    {
        return now()->createFromTimestamp(LARAVEL_START);
    }

    /**
     * Determine if the bot is booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }
}
