<?php

namespace Laracord;

use Discord\DiscordCommandClient as Discord;
use Discord\WebSockets\Intents;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laracord\Commands\Command;
use Laracord\Commands\Components\Message;
use Laracord\Commands\SlashCommand;
use Laracord\Console\Commands\Command as ConsoleCommand;
use Laracord\Events\Event;
use Laracord\Logging\Logger;
use Laracord\Services\Service;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use ReflectionClass;
use Throwable;

use function Laravel\Prompts\table;
use function React\Async\async;
use function React\Async\await;
use function React\Promise\all;

class Laracord
{
    /**
     * The event loop.
     */
    protected $loop;

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
    protected string $prefix = '';

    /**
     * The Discord bot intents.
     */
    protected ?int $intents = null;

    /**
     * The DiscordPHP options.
     */
    protected array $options = [];

    /**
     * The Discord bot commands.
     */
    protected array $commands = [];

    /**
     * The Discord bot slash commands.
     */
    protected array $slashCommands = [];

    /**
     * The Discord events.
     */
    protected array $events = [];

    /**
     * The bot services.
     */
    protected array $services = [];

    /**
     * The bot HTTP server.
     *
     * @var \React\Http\HttpServer
     */
    protected $httpServer;

    /**
     * The registered bot commands.
     */
    protected array $registeredCommands = [];

    /**
     * The registered Discord events.
     */
    protected array $registeredEvents = [];

    /**
     * The registered bot services.
     */
    protected array $registeredServices = [];

    /**
     * Determine whether to show the commands on boot.
     */
    protected bool $showCommands = true;

    /**
     * Initialize the Discord Bot.
     */
    public function __construct(ConsoleCommand $console)
    {
        $this->console = $console;
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

        $this->registerCommands();

        $this->discord()->on('ready', function () {
            $this->registerEvents();
            $this->bootServices();
            $this->bootHttpServer();

            $this->registerSlashCommands()->then(function () {
                $status = collect([
                    'commands' => count($this->registeredCommands),
                    'events' => count($this->registeredEvents),
                    'services' => count($this->registeredServices),
                ])
                    ->filter()
                    ->map(function ($count, $type) {
                        $string = Str::plural($type, $count);

                        return "<fg=blue>{$count}</> {$string}";
                    })->implode(', ');

                $status = Str::replaceLast(', ', ', and ', $status);

                $this->console()->log("Successfully booted <fg=blue>{$this->getName()}</> with {$status}.");

                $this->showCommands();
            });

            $this->afterBoot();
        });

        $this->discord()->run();
    }

    /**
     * Boot the Discord client.
     */
    public function bootDiscord(): void
    {
        $this->discord = new Discord([
            'token' => $this->getToken(),
            'prefix' => $this->getPrefix(),
            'description' => $this->getDescription(),
            'discordOptions' => $this->getOptions(),
            'defaultHelpCommand' => false,
        ]);
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
     * Register the bot commands.
     */
    protected function registerCommands(): void
    {
        foreach ($this->getCommands() as $command) {
            $command = $command::make($this);

            $this->discord->registerCommand($command->getName(), fn ($message, $args) => $command->maybeHandle($message, $args), [
                'cooldown' => $command->getCooldown() ?: 0,
                'cooldownMessage' => $command->getCooldownMessage() ?: '',
                'description' => $command->getDescription() ?: '',
                'usage' => $command->getUsage() ?: '',
                'aliases' => $command->getAliases(),
            ]);

            $this->registeredCommands[] = $command;
        }
    }

    /**
     * Handle the bot slash commands.
     */
    protected function registerSlashCommands()
    {
        $existing = [];

        $existing[] = async(fn () => await($this->discord->application->commands->freshen()))();

        foreach ($this->discord->guilds as $guild) {
            $existing[] = async(fn () => await($guild->commands->freshen()))();
        }

        return all($existing)->then(function ($commands) {
            $existing = collect($commands)
                ->flatMap(fn ($command) => $command->toArray())
                ->map(fn ($command) => collect($command->getUpdatableAttributes())->prepend($command->id, 'id')->filter()->all())
                ->map(fn ($command) => array_merge($command, [
                    'guild_id' => $command['guild_id'] ?? null,
                    'dm_permission' => $command['dm_permission'] ?? null,
                    'default_permission' => $command['default_permission'] ?? true,
                    'options' => collect($command['options'] ?? [])->map(fn ($option) => collect($option)->sortKeys()->all())->all(),
                ]))
                ->keyBy('name');

            $registered = collect($this->getSlashCommands())
                ->mapWithKeys(function ($command) {
                    $command = $command::make($this);
                    $attributes = $command->create()->getUpdatableAttributes();

                    $attributes = array_merge($attributes, [
                        'type' => $attributes['type'] ?? 1,
                        'dm_permission' => $attributes['dm_permission'] ?? null,
                        'guild_id' => $attributes['guild_id'] ?? false,
                    ]);

                    return [$command->getName() => [
                        'state' => $command,
                        'attributes' => $attributes,
                    ]];
                });

            $created = $registered->reject(fn ($command, $name) => $existing->has($name));
            $deleted = $existing->reject(fn ($command, $name) => $registered->has($name));

            $updated = $registered
                ->map(function ($command) {
                    $options = collect($command['attributes']['options'] ?? [])
                        ->filter()
                        ->all();

                    $attributes = collect($command['attributes']);

                    $attributes = $attributes
                        ->put('options', collect($options)->map(fn ($option) => collect($option)->filter()->all())->all())
                        ->forget('guild_id')
                        ->filter()
                        ->prepend($command['state']->getGuild(), 'guild_id')
                        ->all();

                    return array_merge($command, ['attributes' => $attributes]);
                })
                ->filter(function ($command, $name) use ($existing) {
                    if (! $existing->has($name)) {
                        return false;
                    }

                    $current = collect($existing->get($name))->forget('id');

                    foreach ($command['attributes'] as $key => $value) {
                        $attributes = $current->get($key);

                        if (is_array($attributes) && is_array($value)) {
                            $attributes = collect($attributes)
                                ->map(fn ($attribute) => collect($attribute)->sortKeys()->all())
                                ->toJson();

                            $value = collect($value)
                                ->map(fn ($attribute) => collect($attribute)->sortKeys()->all())
                                ->toJson();
                        }

                        if ($attributes === $value) {
                            continue;
                        }

                        return true;
                    }

                    return false;
                })->each(function ($command) use ($existing) {
                    $state = $existing->get($command['state']->getName());

                    if (Arr::get($command, 'attributes.guild_id') && ! Arr::get($state, 'guild_id')) {
                        $this->unregisterSlashCommand($state['id']);
                    }

                    if (! Arr::get($command, 'attributes.guild_id') && $guild = Arr::get($state, 'guild_id')) {
                        $this->unregisterSlashCommand($state['id'], $guild);
                    }
                });

            if ($created->isNotEmpty()) {
                $this->console()->log("Creating <fg=blue>{$created->count()}</> new slash command(s).");

                $created->each(fn ($command) => $this->registerSlashCommand($command['state']));
            }

            if ($updated->isNotEmpty()) {
                $this->console()->warn("Updating <fg=yellow>{$updated->count()}</> slash command(s).");

                $updated->each(fn ($command) => $this->registerSlashCommand($command['state']));
            }

            if ($deleted->isNotEmpty()) {
                $this->console()->warn("Deleting <fg=yellow>{$deleted->count()}</> slash command(s).");

                $deleted->each(fn ($command) => $this->unregisterSlashCommand($command['id'], $command['guild_id'] ?? null));
            }

            if ($registered->isEmpty()) {
                return;
            }

            $registered->each(fn ($command, $name) => $this->discord()->listenCommand($name, fn ($interaction) => $command['state']->maybeHandle($interaction)));

            $this->registeredCommands = array_merge($this->registeredCommands, $registered->pluck('state')->all());
        });
    }

    /**
     * Register the specified slash command.
     */
    public function registerSlashCommand(SlashCommand $command): void
    {
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
     * Unregister the specified slash command.
     */
    public function unregisterSlashCommand(string $id, ?string $guild = null): void
    {
        if ($guild) {
            $guild = $this->discord()->guilds->get('id', $guild);

            if (! $guild) {
                $this->console()->warn("The command with ID <fg=yellow>{$id}</> failed to unregister because the guild <fg=yellow>{$guild}</> could not be found.");

                return;
            }

            $guild->commands->delete($id)->done();

            return;
        }

        $this->discord()->application->commands->delete($id)->done();
    }

    /**
     * Register the Discord events.
     */
    public function registerEvents(): void
    {
        foreach ($this->getEvents() as $event) {
            $event = $event::make($this);

            try {
                $this->registeredEvents[] = $event->register();
            } catch (Exception $e) {
                $this->console()->error("The <fg=red>{$event->getName()}</> event failed to register.");
                $this->console()->error($e->getMessage());

                continue;
            }

            $this->console()->log("The <fg=blue>{$event->getName()}</> event has been registered to <fg=blue>{$event->getHandler()}</>.");
        }
    }

    /**
     * Boot the bot services.
     */
    public function bootServices(): void
    {
        foreach ($this->getServices() as $service) {
            $service = $service::make($this);

            try {
                $this->registeredServices[] = $service->boot();
            } catch (Exception $e) {
                $this->console()->error("The <fg=red>{$service->getName()}</> service failed to boot.");
                $this->console()->error($e->getMessage());

                continue;
            }

            $this->console()->log("The <fg=blue>{$service->getName()}</> service has been booted.");
        }
    }

    /**
     * Boot the HTTP server.
     */
    public function bootHttpServer(): void
    {
        if ($this->httpServer) {
            return;
        }

        $address = config('discord.http');
        $routes = $this->getHttpPath('routes.php');

        if (! $address || ! File::exists($routes)) {
            return;
        }

        if (Str::startsWith($address, ':')) {
            $address = Str::start($address, '0.0.0.0');
        }

        require_once $routes;

        if (! Route::getRoutes()->getRoutes()) {
            return;
        }

        $this->httpServer = new HttpServer($this->getLoop(), function (ServerRequestInterface $request) {
            $headers = $request->getHeaders();
            $request = Request::create($request->getUri()->getPath(), $request->getMethod(), [], [], [], $_SERVER, $request->getBody()->getContents());

            foreach ($headers as $header => $values) {
                $request->headers->set($header, $values);
            }

            app()->instance('request', $request);

            try {
                $response = app('router')->dispatch($request);
            } catch (Throwable $e) {
                $response = 'Internal Server Error';

                if (! app()->isProduction()) {
                    $response = Str::finish($response, ": {$e->getMessage()}");
                }

                return new Response(500, ['Content-Type' => 'text/plain'], $response);
            }

            return new Response(
                $response->getStatusCode(),
                $response->headers->allPreserveCaseWithoutCookies(),
                $response->getContent()
            );
        });

        $socket = new SocketServer($address, [], $this->getLoop());

        $this->httpServer->listen($socket);

        $this->console()->log("HTTP server started on <fg=blue>{$address}</>.");
    }

    /**
     * Print the registered commands to console.
     */
    public function showCommands(): void
    {
        if (! $this->showCommands) {
            return;
        }

        table(
            ['<fg=blue>Command</>', '<fg=blue>Description</>'],
            collect($this->registeredCommands)->map(fn ($command) => [
                $command->getSignature(),
                $command->getDescription(),
            ])->toArray()
        );
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
            'logger' => Logger::make($this->console),
            'loop' => $this->getLoop(),
        ];

        return $this->options = [
            ...config('discord.options', []),
            ...$defaultOptions,
        ];
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
            ->filter(fn ($service) => is_subclass_of($service, Event::class) && ! (new ReflectionClass($service))->isAbstract())
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
            ->filter(fn ($service) => is_subclass_of($service, Service::class) && ! (new ReflectionClass($service))->isAbstract())
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
            ->filter(fn ($command) => is_subclass_of($command, Command::class) && ! (new ReflectionClass($command))->isAbstract())
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
            ->filter(fn ($command) => is_subclass_of($command, SlashCommand::class) && ! (new ReflectionClass($command))->isAbstract())
            ->all();

        return $this->slashCommands = $slashCommands;
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
     * Get the path to the HTTP routes.
     */
    public function getHttpPath(string $path = ''): string
    {
        $path = $path ? Str::start($path, '/') : '';

        return app_path("Http{$path}");
    }

    /**
     * Get the event loop.
     */
    public function getLoop()
    {
        if ($this->loop) {
            return $this->loop;
        }

        return $this->loop = Loop::get();
    }

    /**
     * Get the Discord instance.
     */
    public function discord(): Discord
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
     * Retrieve the prefix.
     */
    public function getPrefix(): string
    {
        if ($this->prefix) {
            return $this->prefix;
        }

        $prefix = trim(config('discord.prefix'));

        if (! $prefix || $prefix === '/') {
            throw new Exception('You must provide a valid command prefix.');
        }

        return $this->prefix = $prefix;
    }

    /**
     * Build an embed for use in a Discord message.
     *
     * @param  string  $content
     * @return \Laracord\Commands\Components\Message
     */
    public function message($content = '')
    {
        return Message::make($this)
            ->content($content);
    }
}
