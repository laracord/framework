<?php

namespace Laracord;

use Carbon\Carbon;
use Discord\DiscordCommandClient as Discord;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event as DiscordEvent;
use Discord\WebSockets\Intents;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laracord\Commands\ApplicationCommand;
use Laracord\Commands\Command;
use Laracord\Commands\ContextMenu;
use Laracord\Commands\SlashCommand;
use Laracord\Concerns\CanAsync;
use Laracord\Console\Commands\Command as ConsoleCommand;
use Laracord\Discord\Message;
use Laracord\Events\Event;
use Laracord\Http\Server;
use Laracord\Logging\Logger;
use Laracord\Services\Service;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;
use ReflectionClass;
use Throwable;

use function React\Async\await;
use function React\Promise\all;

class Laracord
{
    use CanAsync;

    /**
     * The event loop.
     */
    protected ?LoopInterface $loop = null;

    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The console instance.
     *
     * @var \Laracord\Console\Commands\Command
     */
    protected $console;

    /**
     * The Discord instance.
     *
     * @var \Discord\DiscordCommandClient
     */
    protected $discord;

    /**
     * The Discord bot name.
     */
    protected string $name = '';

    /**
     * The Discord bot description.
     */
    protected string $description = '';

    /**
     * The Discord bot token.
     */
    protected string $token = '';

    /**
     * The Discord bot command prefix.
     */
    protected ?Collection $prefixes = null;

    /**
     * The Discord bot intents.
     */
    protected ?int $intents = null;

    /**
     * The DiscordPHP options.
     */
    protected array $options = [];

    /**
     * The Discord bot admins.
     */
    protected array $admins = [];

    /**
     * The Discord bot commands.
     */
    protected array $commands = [];

    /**
     * The Discord bot slash commands.
     */
    protected array $slashCommands = [];

    /**
     * The Discord bot context menus.
     */
    protected array $contextMenus = [];

    /**
     * The Discord events.
     */
    protected array $events = [];

    /**
     * The bot services.
     */
    protected array $services = [];

    /**
     * The console input stream.
     *
     * @var \React\Stream\ReadableResourceStream
     */
    protected $inputStream;

    /**
     * The console output stream.
     *
     * @var \React\Stream\WritableResourceStream
     */
    protected $outputStream;

    /**
     * The bot HTTP server.
     *
     * @var \Laracord\Http\Server
     */
    protected $httpServer;

    /**
     * The logger instance.
     *
     * @var \Laracord\Logging\Logger
     */
    protected $logger;

    /**
     * The registered bot commands.
     */
    protected array $registeredCommands = [];

    /**
     * The registered context menus.
     */
    protected array $registeredContextMenus = [];

    /**
     * The registered Discord events.
     */
    protected array $registeredEvents = [];

    /**
     * The registered bot services.
     */
    protected array $registeredServices = [];

    /**
     * The registered bot interaction routes.
     */
    protected array $registeredInteractions = [];

    /**
     * Determine whether to show the commands on boot.
     */
    protected bool $showCommands = true;

    /**
     * Show the invite link if the bot is not in any guilds.
     */
    protected bool $showInvite = true;

    /**
     * Initialize the Discord Bot.
     */
    public function __construct(ConsoleCommand $console)
    {
        $this->console = $console;
        $this->app = $console->getLaravel();
        $this->admins = config('discord.admins', $this->admins);
    }

    /**
     * Make the Bot instance.
     */
    public static function make(ConsoleCommand $console): self
    {
        return new static($console);
    }

    /**
     * Boot the bot.
     */
    public function boot(): void
    {
        $this->beforeBoot();

        $this->bootDiscord();

        $this->registerStream();

        $this->registerCommands();

        $this->discord()->on('init', function () {
            $this
                ->registerEvents()
                ->bootServices()
                ->bootHttpServer()
                ->registerApplicationCommands()
                ->handleInteractions();

            $this->afterBoot();

            $this->getLoop()->addTimer(1, function () {
                $status = $this
                    ->getStatus()
                    ->map(fn ($count, $type) => "<fg=blue>{$count}</> {$type}")
                    ->implode(', ');

                $status = Str::replaceLast(', ', ', and ', $status);

                $this->console()->log("Successfully booted <fg=blue>{$this->getName()}</> with {$status}.");

                $this
                    ->showCommands()
                    ->showInvite();
            });
        });

        $this->discord()->run();
    }

    /**
     * Boot the Discord client.
     */
    protected function bootDiscord(): void
    {
        $this->discord = new Discord([
            'token' => $this->getToken(),
            'prefixes' => $this->getPrefixes()->all(),
            'description' => $this->getDescription(),
            'discordOptions' => $this->getOptions(),
            'defaultHelpCommand' => false,
        ]);
    }

    /**
     * Register the input and output streams.
     */
    protected function registerStream(): self
    {
        if (windows_os()) {
            return $this;
        }

        if ($this->inputStream && $this->outputStream) {
            return $this;
        }

        $this->inputStream = new ReadableResourceStream(STDIN, $this->getLoop());
        $this->outputStream = new WritableResourceStream(STDOUT, $this->getLoop());

        $this->inputStream->on('data', fn ($data) => $this->handleStream($data));

        return $this;
    }

    /**
     * Handle the input stream.
     */
    protected function handleStream(string $data): void
    {
        $command = trim($data);

        if (! $command) {
            $this->outputStream->write('> ');

            return;
        }

        $this->console()->newLine();

        match ($command) {
            'shutdown', 'exit', 'quit', 'stop' => $this->shutdown(),
            'restart' => $this->restart(),
            'invite' => $this->showInvite(force: true),
            'commands' => $this->showCommands(),
            'status' => $this->showStatus(),
            '?' => $this->console()->table(['<fg=blue>Command</>', '<fg=blue>Description</>'], [
                ['shutdown', 'Shutdown the bot.'],
                ['restart', 'Restart the bot.'],
                ['invite', 'Show the invite link.'],
                ['commands', 'Show the registered commands.'],
                ['status', 'Show the bot status.'],
            ]),
            default => $this->console()->error("Unknown command: <fg=red>{$command}</>"),
        };

        $this->outputStream->write('> ');
    }

    /**
     * Shutdown the bot.
     */
    public function shutdown(int $code = 0): void
    {
        $this->console()->log("Shutting down <fg=blue>{$this->getName()}</>.");

        $this->httpServer()->shutdown();
        $this->discord()->close();

        exit($code);
    }

    /**
     * Restart the bot.
     */
    public function restart(): void
    {
        $this->console()->log("<fg=blue>{$this->getName()}</> is restarting.");

        $this->httpServer()->shutdown();
        $this->discord()->close();

        $this->httpServer = null;
        $this->discord = null;

        $this->registeredCommands = [];
        $this->registeredContextMenus = [];
        $this->registeredEvents = [];
        $this->registeredServices = [];

        $this->boot();
    }

    /**
     * Actions to run before booting the bot.
     */
    public function beforeBoot(): void
    {
        //
    }

    /**
     * Actions to run after booting the bot.
     */
    public function afterBoot(): void
    {
        //
    }

    /**
     * The HTTP routes.
     */
    public function routes(): void
    {
        //
    }

    /**
     * The HTTP middleware.
     */
    public function middleware(): array
    {
        return [];
    }

    /**
     * The prepended HTTP middleware.
     */
    public function prependMiddleware(): array
    {
        return [];
    }

    /**
     * Register the bot commands.
     */
    protected function registerCommands(): self
    {
        foreach ($this->getCommands() as $command) {
            $command = $command::make($this);

            if (! $command->isEnabled()) {
                continue;
            }

            $options = [
                'cooldown' => $command->getCooldown() ?: 0,
                'cooldownMessage' => $command->getCooldownMessage() ?: '',
                'description' => $command->getDescription() ?: '',
                'usage' => $command->getUsage() ?: '',
                'aliases' => $command->getAliases(),
            ];

            $this->discord->registerCommand(
                $command->getName(),
                fn ($message, $args) => $this->handleSafe($command->getName(), fn () => $command->maybeHandle($message, $args)),
                $options
            );

            $this->registeredCommands[] = $command;

            $this->registerInteractions($command->getName(), $command->interactions());
        }

        return $this;
    }

    /**
     * Register the bot application commands.
     */
    protected function registerApplicationCommands(): self
    {
        $normalize = function ($data) use (&$normalize) {
            if (is_object($data)) {
                $data = (array) $data;
            }

            if (is_array($data)) {
                ksort($data);

                return array_map($normalize, $data);
            }

            return $data;
        };

        $existing = cache()->get('laracord.application-commands', []);

        if (! $existing) {
            $existing[] = $this->discord->application->commands->freshen();

            foreach ($this->discord->guilds as $guild) {
                $existing[] = $guild->commands->freshen();
            }

            $existing = all($existing)->then(fn ($commands) => collect($commands)
                ->flatMap(fn ($command) => $command->toArray())
                ->map(fn ($command) => collect($command->getCreatableAttributes())
                    ->merge([
                        'id' => $command->id,
                        'guild_id' => $command->guild_id ?? null,
                        'dm_permission' => $command->guild_id ? null : ($command->dm_permission ?? false),
                        'default_permission' => $command->default_permission ?? true,
                    ])
                    ->all()
                )
                ->map(fn ($command) => array_merge($command, [
                    'options' => json_decode(json_encode($command['options'] ?? []), true),
                ]))
                ->filter(fn ($command) => ! blank($command))
                ->keyBy('name')
            );

            $existing = await($existing);

            cache()->forever('laracord.application-commands', $existing);
        }

        $existing = collect($existing);

        $registered = collect($this->getSlashCommands())
            ->merge($this->getContextMenus())
            ->map(fn ($command) => $command::make($this))
            ->filter(fn ($command) => $command->isEnabled())
            ->mapWithKeys(function ($command) {
                $attributes = $command->create()->getCreatableAttributes();

                $attributes = collect($attributes)
                    ->merge([
                        'guild_id' => $command->getGuild() ?? null,
                        'dm_permission' => ! $command->getGuild() ? $command->canDirectMessage() : null,
                        'nsfw' => $command->isNsfw(),
                    ])
                    ->sortKeys()
                    ->all();

                return [$command->getName() => [
                    'state' => $command,
                    'attributes' => $attributes,
                ]];
            });

        $created = $registered->reject(fn ($command, $name) => $existing->has($name))->filter();
        $deleted = $existing->reject(fn ($command, $name) => $registered->has($name))->filter();

        $updated = $registered
            ->map(function ($command) {
                $attributes = collect($command['attributes'])
                    ->reject(fn ($value) => blank($value))
                    ->all();

                return array_merge($command, ['attributes' => $attributes]);
            })
            ->filter(function ($command, $name) use ($existing, $normalize) {
                if (! $existing->has($name)) {
                    return false;
                }

                $current = collect($existing->get($name))
                    ->forget('id')
                    ->reject(fn ($value) => blank($value));

                $attributes = collect($command['attributes'])
                    ->reject(fn ($value) => blank($value));

                $keys = collect($current->keys())
                    ->merge($attributes->keys())
                    ->unique();

                foreach ($keys as $key) {
                    $attribute = $current->get($key);
                    $value = $attributes->get($key);

                    $attribute = $normalize($attribute);
                    $value = $normalize($value);

                    if ($attribute === $value) {
                        continue;
                    }

                    return true;
                }

                return false;
            })
            ->each(function ($command) use ($existing) {
                $state = $existing->get($command['state']->getName());

                $current = Arr::get($command, 'attributes.guild_id');
                $existing = Arr::get($state, 'guild_id');

                if ($current && ! $existing) {
                    $this->unregisterApplicationCommand($state['id']);
                }

                if ((! $current && $existing) || $current !== $existing) {
                    $this->unregisterApplicationCommand($state['id'], $existing);
                }
            });

        if ($updated->isNotEmpty()) {
            $this->console()->warn("Updating <fg=yellow>{$updated->count()}</> application command(s).");

            $updated->each(function ($command) {
                $state = $command['state'];

                $this->registerApplicationCommand($state);
            });
        }

        if ($deleted->isNotEmpty()) {
            $this->console()->warn("Deleting <fg=yellow>{$deleted->count()}</> application command(s).");

            $deleted->each(fn ($command) => $this->unregisterApplicationCommand($command['id'], $command['guild_id'] ?? null));
        }

        if ($created->isNotEmpty()) {
            $this->console()->log("Creating <fg=blue>{$created->count()}</> new application command(s).");

            $created->each(fn ($command) => $this->registerApplicationCommand($command['state']));
        }

        if ($registered->isEmpty()) {
            return $this;
        }

        $registered->each(function ($command, $name) {
            $this->registerInteractions($name, $command['state']->interactions());

            if ($command['state'] instanceof ContextMenu) {
                $this->discord()->listenCommand(
                    $name,
                    fn ($interaction) => $this->handleSafe($name, fn () => $command['state']->maybeHandle($interaction))
                );

                $this->registeredContextMenus[] = $command['state'];

                return;
            }

            $subcommands = collect($command['state']->getRegisteredOptions())
                ->filter(fn (Option $option) => $option->type === Option::SUB_COMMAND)
                ->map(fn (Option $subcommand) => [$name, $subcommand->name]);

            $subcommandGroups = collect($command['state']->getRegisteredOptions())
                ->filter(fn (Option $option) => $option->type === Option::SUB_COMMAND_GROUP)
                ->flatMap(fn (Option $group) => collect($group->options)
                    ->filter(fn (Option $subcommand) => $subcommand->type === Option::SUB_COMMAND)
                    ->map(fn (Option $subcommand) => [$name, $group->name, $subcommand->name])
                );

            $subcommands = $subcommands->merge($subcommandGroups);

            if ($subcommands->isNotEmpty()) {
                $subcommands->each(function ($names) use ($command, $name) {
                    $this->discord()->listenCommand(
                        $names,
                        fn ($interaction) => $this->handleSafe($name, fn () => $command['state']->maybeHandle($interaction)),
                        fn ($interaction) => $this->handleSafe($name, fn () => $command['state']->maybeHandleAutocomplete($interaction))
                    );
                });

                return;
            }

            $this->discord()->listenCommand(
                $name,
                fn ($interaction) => $this->handleSafe($name, fn () => $command['state']->maybeHandle($interaction)),
                fn ($interaction) => $this->handleSafe($name, fn () => $command['state']->maybeHandleAutocomplete($interaction))
            );
        });

        $this->registeredCommands = array_merge(
            $this->registeredCommands,
            $registered->pluck('state')->reject(fn ($command) => $command instanceof ContextMenu)->all()
        );

        return $this;
    }

    /**
     * Register the specified application command.
     */
    public function registerApplicationCommand(ApplicationCommand $command): void
    {
        cache()->forget('laracord.application-commands');

        if ($command->getGuild()) {
            $guild = $this->discord()->guilds->get('id', $command->getGuild());

            if (! $guild) {
                $this->console()->warn("The <fg=yellow>{$command->getName()}</> command failed to register because the guild <fg=yellow>{$command->getGuild()}</> could not be found.");

                return;
            }

            $guild->commands->save($command->create());

            return;
        }

        $this->discord()->application->commands->save($command->create());
    }

    /**
     * Unregister the specified application command.
     */
    public function unregisterApplicationCommand(string $id, ?string $guildId = null): void
    {
        cache()->forget('laracord.application-commands');

        if ($guildId) {
            $guild = $this->discord()->guilds->get('id', $guildId);

            if (! $guild) {
                $this->console()->warn("The command with ID <fg=yellow>{$id}</> failed to unregister because the guild <fg=yellow>{$guildId}</> could not be found.");

                return;
            }

            $guild->commands->delete($id)->done();

            return;
        }

        $this->discord()->application->commands->delete($id)->done();
    }

    /**
     * Register the interaction routes.
     */
    protected function registerInteractions(string $name, array $routes = []): void
    {
        $routes = collect($routes)
            ->mapWithKeys(fn ($value, $route) => ["{$name}@{$route}" => $value])
            ->all();

        if (! $routes) {
            return;
        }

        $this->registeredInteractions = array_merge($this->registeredInteractions, $routes);
    }

    /**
     * Register the Discord events.
     */
    protected function registerEvents(): self
    {
        foreach ($this->getEvents() as $event) {
            $this->handleSafe($event, function () use ($event) {
                $event = $event::make($this);

                if (! $event->isEnabled()) {
                    return;
                }

                $this->registeredEvents[] = $event->register();

                $this->console()->log("The <fg=blue>{$event->getName()}</> event has been registered to <fg=blue>{$event->getHandler()}</>.");
            });
        }

        return $this;
    }

    /**
     * Boot the bot services.
     */
    protected function bootServices(): self
    {
        foreach ($this->getServices() as $service) {
            $this->handleSafe($service, function () use ($service) {
                $service = $service::make($this);

                if (! $service->isEnabled()) {
                    return;
                }

                $this->registeredServices[] = $service->boot();

                $this->console()->log("The <fg=blue>{$service->getName()}</> service has been booted.");
            });
        }

        return $this;
    }

    /**
     * Handle the interaction routes.
     */
    protected function handleInteractions(): self
    {
        $this->discord()->on(DiscordEvent::INTERACTION_CREATE, function (Interaction $interaction) {
            $id = $interaction->data->custom_id;

            $handlers = collect($this->getRegisteredInteractions())
                ->partition(fn ($route, $name) => ! Str::contains($name, '{'));

            $static = $handlers[0];
            $dynamic = $handlers[1];

            if ($route = $static->get($id)) {
                return $this->handleSafe($id, fn () => $route($interaction));
            }

            if (! $route) {
                $route = $dynamic->first(fn ($route, $name) => Str::before($name, ':') === Str::before($id, ':'));
            }

            if (! $route) {
                return;
            }

            $parameters = [];
            $requiredParameters = [];

            if (Str::contains($id, ':')) {
                $parameters = explode(':', Str::after($id, ':'));
            }

            $routeName = $dynamic->keys()->first(fn ($name) => Str::before($name, ':') === Str::before($id, ':'));

            if ($routeName && preg_match_all('/\{(.*?)\}/', $routeName, $matches)) {
                $requiredParameters = $matches[1];
            }

            foreach ($requiredParameters as $index => $param) {
                if (! Str::endsWith($param, '?') && (! isset($parameters[$index]) || $parameters[$index] === '')) {
                    $this->console()->error("Missing required parameter `{$param}` for interaction route `{$routeName}`.");

                    return;
                }
            }

            $this->handleSafe($id, fn () => $route($interaction, ...$parameters));
        });

        return $this;
    }

    /**
     * Boot the HTTP server.
     */
    protected function bootHttpServer(): self
    {
        if ($this->httpServer) {
            return $this;
        }

        $this->handleSafe(Server::class, function () {
            $this->routes();

            $this->app->booted(function () {
                $this->app['router']->getRoutes()->refreshNameLookups();
                $this->app['router']->getRoutes()->refreshActionLookups();
            });

            $this->httpServer = Server::make($this)->boot();

            if ($this->httpServer->isBooted()) {
                $this->console()->log("HTTP server started on <fg=blue>{$this->httpServer->getAddress()}</>.");
            }
        });

        return $this;
    }

    /**
     * Print the registered commands to console.
     */
    public function showCommands(): self
    {
        if (! $this->showCommands) {
            return $this;
        }

        $this->console()->table(
            ['<fg=blue>Command</>', '<fg=blue>Description</>'],
            collect($this->getRegisteredCommands())->map(fn ($command) => [
                $command->getSignature(),
                $command->getDescription(),
            ])->toArray()
        );

        return $this;
    }

    /**
     * Print the bot status to console.
     */
    public function showStatus(): self
    {
        $uptime = now()->createFromTimestamp(LARAVEL_START)->diffForHumans();

        $status = $this->getStatus()
            ->prepend($this->discord()->users->count(), 'user')
            ->prepend($this->discord()->guilds->count(), 'guild')
            ->mapWithKeys(fn ($count, $type) => [Str::plural($type, $count) => $count])
            ->prepend($uptime, 'uptime')
            ->prepend("{$this->discord()->username} ({$this->discord()->id})", 'bot')
            ->map(fn ($count, $type) => Str::of($type)->title()->finish(": <fg=blue>{$count}</>")->toString());

        $this->console()->line('  <options=bold>Status</>');
        $this->console()->outputComponents()->bulletList($status->all());

        return $this;
    }

    /**
     * Show the invite link if the bot is not in any guilds.
     */
    public function showInvite(bool $force = false): self
    {
        if (! $force && (! $this->showInvite || $this->discord()->guilds->count() > 0)) {
            return $this;
        }

        if (! $force) {
            $this->console()->warn("{$this->getName()} is currently not in any guilds.");
        }

        $query = Arr::query([
            'client_id' => $this->discord()->id,
            'permissions' => 281600,
            'scope' => 'bot applications.commands',
        ]);

        $this->console()->log("You can <fg=blue>invite {$this->getName()}</> using the following link:");

        $this->console()->outputComponents()->bulletList(["https://discord.com/api/oauth2/authorize?{$query}"]);

        return $this;
    }

    /**
     * Get the bot name.
     */
    public function getName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        return $this->name = config('app.name');
    }

    /**
     * Get the bot description.
     */
    public function getDescription(): string
    {
        if ($this->description) {
            return $this->description;
        }

        return $this->description = config('discord.description');
    }

    /**
     * Get the bot token.
     */
    public function getToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        $token = config('discord.token');

        if (! $token) {
            throw new Exception('You must provide a Discord bot token.');
        }

        return $this->token = $token;
    }

    /**
     * Get the bot intents.
     */
    public function getIntents(): ?int
    {
        if ($this->intents) {
            return $this->intents;
        }

        return $this->intents = config('discord.intents', Intents::getDefaultIntents());
    }

    /**
     * Get the bot options.
     */
    public function getOptions(): array
    {
        if ($this->options) {
            return $this->options;
        }

        $defaultOptions = [
            'intents' => $this->getIntents(),
            'logger' => $this->getLogger(),
            'loop' => $this->getLoop(),
        ];

        return $this->options = [
            ...config('discord.options', []),
            ...$defaultOptions,
        ];
    }

    /**
     * Get the logger instance.
     */
    public function getLogger(): Logger
    {
        return $this->logger ??= Logger::make($this->console);
    }

    /**
     * Get the Discord admins.
     */
    public function getAdmins(): array
    {
        return $this->admins;
    }

    /**
     * Get the Discord events.
     */
    public function getEvents(): array
    {
        if ($this->events) {
            return $this->events;
        }

        $events = $this->extractClasses($this->getEventPath())
            ->merge(config('discord.events', []))
            ->unique()
            ->filter(fn ($event) => $this->handleSafe($event, fn () => is_subclass_of($event, Event::class) && ! (new ReflectionClass($event))->isAbstract()))
            ->all();

        return $this->events = $events;
    }

    /**
     * Get the bot services.
     */
    public function getServices(): array
    {
        if ($this->services) {
            return $this->services;
        }

        $services = $this->extractClasses($this->getServicePath())
            ->merge(config('discord.services', []))
            ->unique()
            ->filter(fn ($service) => $this->handleSafe($service, fn () => is_subclass_of($service, Service::class) && ! (new ReflectionClass($service))->isAbstract()))
            ->all();

        return $this->services = $services;
    }

    /**
     * Get the bot commands.
     */
    public function getCommands(): array
    {
        if ($this->commands) {
            return $this->commands;
        }

        $commands = $this->extractClasses($this->getCommandPath())
            ->merge(config('discord.commands', []))
            ->unique()
            ->filter(fn ($command) => $this->handleSafe($command, fn () => is_subclass_of($command, Command::class) && ! (new ReflectionClass($command))->isAbstract()))
            ->all();

        return $this->commands = $commands;
    }

    /**
     * Get the bot slash commands.
     */
    public function getSlashCommands(): array
    {
        if ($this->slashCommands) {
            return $this->slashCommands;
        }

        $slashCommands = $this->extractClasses($this->getSlashCommandPath())
            ->merge(config('discord.commands', []))
            ->unique()
            ->filter(fn ($command) => $this->handleSafe($command, fn () => is_subclass_of($command, SlashCommand::class) && ! (new ReflectionClass($command))->isAbstract()))
            ->all();

        return $this->slashCommands = $slashCommands;
    }

    /**
     * Get the bot context menus.
     */
    public function getContextMenus(): array
    {
        if ($this->contextMenus) {
            return $this->contextMenus;
        }

        $contextMenus = $this->extractClasses($this->getContextMenuPath())
            ->merge(config('discord.menus', []))
            ->unique()
            ->filter(fn ($contextMenu) => $this->handleSafe($contextMenu, fn () => is_subclass_of($contextMenu, ContextMenu::class) && ! (new ReflectionClass($contextMenu))->isAbstract()))
            ->all();

        return $this->contextMenus = $contextMenus;
    }

    /**
     * Extract classes from the provided application path.
     */
    protected function extractClasses(string $path): Collection
    {
        if (! File::isDirectory($path)) {
            return collect();
        }

        return collect(File::allFiles($path))
            ->map(function ($file) {
                $relativePath = str_replace(
                    Str::finish(app_path(), DIRECTORY_SEPARATOR),
                    '',
                    $file->getPathname()
                );

                $folders = Str::beforeLast(
                    $relativePath,
                    DIRECTORY_SEPARATOR
                ).DIRECTORY_SEPARATOR;

                $className = Str::after($relativePath, $folders);

                $class = app()->getNamespace().str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    $folders.$className
                );

                return $class;
            });
    }

    /**
     * Get the registered commands.
     */
    public function getRegisteredCommands(): array
    {
        return $this->registeredCommands;
    }

    /**
     * Get the registered context menus.
     */
    public function getRegisteredContextMenus(): array
    {
        return $this->registeredContextMenus;
    }

    /**
     * Get the registered events.
     */
    public function getRegisteredEvents(): array
    {
        return $this->registeredEvents;
    }

    /**
     * Get the registered services.
     */
    public function getRegisteredServices(): array
    {
        return $this->registeredServices;
    }

    /**
     * Get the registered interactions.
     */
    public function getRegisteredInteractions(): array
    {
        return $this->registeredInteractions;
    }

    /**
     * Get a registered command by name.
     */
    public function getCommand(string $name): Command|SlashCommand|null
    {
        return collect($this->getRegisteredCommands())
            ->first(fn ($command) => $command->getName() === $name);
    }

    /**
     * Get a registered context menu by name.
     */
    public function getContextMenu(string $name): ?ContextMenu
    {
        return collect($this->getRegisteredContextMenus())
            ->first(fn ($contextMenu) => $contextMenu->getName() === $name);
    }

    /**
     * Get the path to the Discord commands.
     */
    public function getCommandPath(): string
    {
        return app_path('Commands');
    }

    /**
     * Get the path to the Discord slash commands.
     */
    public function getSlashCommandPath(): string
    {
        return app_path('SlashCommands');
    }

    /**
     * Get the path to the Discord context menus.
     */
    public function getContextMenuPath(): string
    {
        return app_path('Menus');
    }

    /**
     * Get the path to the Discord events.
     */
    public function getEventPath(): string
    {
        return app_path('Events');
    }

    /**
     * Get the path to the bot services.
     */
    public function getServicePath(): string
    {
        return app_path('Services');
    }

    /**
     * Retrieve the prefixes.
     */
    public function getPrefixes(): Collection
    {
        if ($this->prefixes) {
            return $this->prefixes;
        }

        $prefixes = collect(config('discord.prefix', '!'))
            ->filter()
            ->reject(fn ($prefix) => Str::startsWith($prefix, '/'));

        if ($prefixes->isEmpty()) {
            throw new Exception('You must provide a valid command prefix.');
        }

        return $this->prefixes = $prefixes;
    }

    /**
     * Retrieve the primary prefix.
     */
    public function getPrefix(): string
    {
        return $this->getPrefixes()->first();
    }

    /**
     * Get the event loop.
     */
    public function getLoop(): LoopInterface
    {
        if ($this->loop) {
            return $this->loop;
        }

        return $this->loop = Loop::get();
    }

    /**
     * Get the Discord instance.
     */
    public function discord(): ?Discord
    {
        return $this->discord;
    }

    /**
     * Get the console instance.
     */
    public function console(): ConsoleCommand
    {
        return $this->console;
    }

    /**
     * Get the HTTP server instance.
     */
    public function httpServer(): ?Server
    {
        return $this->httpServer;
    }

    /**
     * Get the Application instance.
     */
    public function getApplication(): Application
    {
        return $this->app;
    }

    /**
     * Retrieve the bot status collection.
     */
    public function getStatus(): Collection
    {
        return collect([
            'command' => count($this->getRegisteredCommands()),
            'menu' => count($this->getRegisteredContextMenus()),
            'event' => count($this->getRegisteredEvents()),
            'service' => count($this->getRegisteredServices()),
            'interaction' => count($this->getRegisteredInteractions()),
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
     * Safely handle the provided callback.
     */
    public function handleSafe(string $name, callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            $this->console()->error("An error occurred in <fg=red>{$name}</>.");

            $this->console()->outputComponents()->bulletList([
                sprintf('%s in %s:%d', $e->getMessage(), $e->getFile(), $e->getLine()),
            ]);
        }

        return null;
    }

    /**
     * Build an embed for use in a Discord message.
     *
     * @param  string  $content
     * @return \Laracord\Discord\Message
     */
    public function message($content = '')
    {
        return Message::make($this)
            ->content($content);
    }
}
