<?php

namespace Laracord\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Laracord\Laracord
 *
 * @method static void boot() Boot the bot
 * @method static void shutdown(int $code = 0) Shutdown the bot
 * @method static void restart() Restart the bot
 * @method static \Illuminate\Support\Collection getStatus() Retrieve the bot status collection
 * @method static \Carbon\Carbon getUptime() Retrieve the bot uptime
 * @method static bool isBooted() Determine if the bot is booted
 *
 * @method static \Illuminate\Support\Collection getPrefixes() Retrieve the command prefixes
 * @method static string getPrefix() Retrieve the primary prefix
 * @method static self registerCommand(\Laracord\Commands\Command|string $command) Register a command
 * @method static self registerCommands(array $commands) Register multiple commands
 * @method static self discoverCommands(string $in, string $for) Discover commands in a path
 * @method static array getCommands() Get the registered commands
 * @method static ?\Laracord\Commands\Command getCommand(string $name) Get a registered command by name
 *
 * @method static self registerSlashCommand(\Laracord\Commands\SlashCommand|string $command) Register a slash command
 * @method static self registerSlashCommands(array $commands) Register multiple slash commands
 * @method static self discoverSlashCommands(string $in, string $for) Discover slash commands in a path
 * @method static ?\Laracord\Commands\SlashCommand getSlashCommand(string $name) Get a registered slash command by name
 * @method static array getSlashCommands() Get the registered slash commands
 *
 * @method static self registerContextMenu(\Laracord\Commands\ContextMenu|string $menu) Register a context menu
 * @method static self registerContextMenus(array $menus) Register multiple context menus
 * @method static self discoverContextMenus(string $in, string $for) Discover context menus in a path
 * @method static array getContextMenus() Get the registered context menus
 * @method static ?\Laracord\Commands\ContextMenu getContextMenu(string $name) Get a registered context menu by name
 *
 * @method static self registerEvent(\Laracord\Events\Event|string $event) Register an event
 * @method static self registerEvents(array $events) Register multiple events
 * @method static self discoverEvents(string $in, string $for) Discover events in a path
 * @method static array getEvents() Get the registered events
 * @method static ?\Laracord\Events\Event getEvent(string $name) Get a registered event by name
 *
 * @method static self registerService(\Laracord\Services\Service|string $service) Register a service
 * @method static self registerServices(array $services) Register multiple services
 * @method static self discoverServices(string $in, string $for) Discover services in a path
 * @method static array getServices() Get the registered services
 * @method static ?\Laracord\Services\Service getService(string $name) Get a registered service by name
 *
 * @method static self registerPrompt(\Laracord\Console\Prompts\Prompt|string $prompt) Register a console prompt
 * @method static self registerPrompts(array $prompts) Register multiple console prompts
 * @method static array getPrompts() Get the registered prompts
 * @method static ?\Laracord\Console\Console console() Get the console instance
 *
 * @method static self registerCommandMiddleware(string|\Laracord\Commands\Middleware\Middleware $middleware) Register a global command middleware
 * @method static self registerCommandMiddlewares(array $middlewares) Register multiple global command middleware
 * @method static array getCommandMiddleware() Get the global command middleware
 *
 * @method static self registerInteractionMiddleware(string|\Laracord\Commands\Middleware\Middleware $middleware) Register an interaction middleware
 * @method static self registerInteractionMiddlewares(array $middlewares) Register multiple interaction middleware
 * @method static array getInteractionMiddleware() Get the interaction middleware
 *
 * @method static self withRoutes(?callable $callback = null) Register HTTP routes
 * @method static self withMiddleware(?callable $callback = null) Register HTTP middleware
 *
 * @method static self plugin(\Laracord\Contracts\Plugin $plugin) Register a plugin
 * @method static self plugins(array $plugins) Register multiple plugins
 * @method static array getPlugins() Get the registered plugins
 * @method static ?\Laracord\Contracts\Plugin getPlugin(string $plugin) Retrieve a registered plugin
 *
 * @method static string getName() Get the bot name
 * @method static self setToken(string $token) Set the bot token
 * @method static string getToken() Get the bot token
 * @method static ?int getIntents() Get the bot intents
 * @method static self setShard(int $id, int $count) Set the bot shard ID
 * @method static array getOptions() Get the bot options
 * @method static array getAdmins() Get the Discord admins
 * @method static ?\Discord\Discord discord() Retrieve the Discord instance
 * @method static \Laracord\Discord\Message message(string $content = '') Build a message for Discord
 *
 * @method static \React\EventLoop\LoopInterface getLoop() Get the event loop
 */
class Laracord extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'bot';
    }
}
