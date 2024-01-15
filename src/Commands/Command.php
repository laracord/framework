<?php

namespace Laracord\Commands;

use App\Models\User;
use Laracord\Commands\Components\Message;
use Laracord\Commands\Contracts\Command as CommandContract;
use Laracord\Laracord;

abstract class Command implements CommandContract
{
    /**
     * The bot instance.
     *
     * @var \Laracord\Laracord
     */
    protected $bot;

    /**
     * The console instance.
     *
     * @var \LaravelZero\Framework\Commands\Command
     */
    protected $console;

    /**
     * The Discord instance.
     *
     * @var \Discord\DiscordCommandClient;
     */
    protected $discord;

    /**
     * The user instance.
     *
     * @var \App\Models\User
     */
    protected $user;

    /**
     * The server instance.
     *
     * @var \Discord\Parts\Guild\Guild
     */
    protected $server;

    /**
     * The Discord command name.
     *
     * @var string
     */
    protected $name;

    /**
     * The Discord command aliases.
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * The Discord command description.
     *
     * @var string|null
     */
    protected $description;

    /**
     * The Discord command cooldown.
     *
     * @var int
     */
    protected $cooldown = 0;

    /**
     * The Discord command cooldown message.
     *
     * @var string
     */
    protected $cooldownMessage = '';

    /**
     * The Discord command usage.
     *
     * @var string
     */
    protected $usage = '';

    /**
     * Indiciates whether the command requires admin permissions.
     *
     * @var bool
     */
    protected $admin = false;

    /**
     * Indicates whether the command should be displayed in the commands list.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct(Laracord $bot)
    {
        $this->bot = $bot;
        $this->console = $bot->getConsole();
        $this->discord = $bot->getDiscord();
    }

    /**
     * Maybe handle the Discord command.
     *
     * @param  \Discord\Parts\Channel\Message  $message
     * @param  array  $args
     * @return mixed
     */
    public function maybeHandle($message, $args)
    {
        $this->user = User::firstOrCreate(['discord_id' => $message->author->id], [
            'discord_id' => $message->author->id,
            'username' => $message->author->username,
        ]);

        $this->server = $message->channel->guild;

        if ($this->isAdminCommand() && ! $this->user->is_admin) {
            return;
        }

        $this->handle($message, $args);
    }

    /**
     * Handle the Discord command.
     *
     * @param  \Discord\Parts\Channel\Message  $message
     * @param  array  $args
     * @return mixed
     */
    abstract public function handle($message, $args);

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

    /**
     * Send a log to console.
     *
     * @param  string  $message
     * @return void
     */
    public function log($message)
    {
        return $this->console->info($message);
    }

    /**
     * Get the command user.
     *
     * @param  \Discord\Parts\User\User|null  $user
     * @return \App\Models\User
     */
    public function getUser($user = null)
    {
        return $user ? User::firstOrCreate(['discord_id' => $user->id], [
            'discord_id' => $user->id,
            'username' => $user->username,
        ]) : $this->user;
    }

    /**
     * Get the command server.
     *
     * @return \Discord\Parts\Guild\Guild
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Resolve a Discord user.
     *
     * @param  string  $username
     * @return \App\Models\User|null
     */
    public function resolveUser($username = null)
    {
        return ! empty($username) ? $this->getServer()->members->filter(function ($member) use ($username) {
            $username = str_replace(['<', '@', '>'], '', strtolower($username));

            return ($member->user->username === $username || $member->user->id === $username) && ! $member->user->bot;
        })->first() : null;
    }

    /**
     * Retrieve the command name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Retrieve the full command syntax.
     *
     * @return string
     */
    public function getSyntax()
    {
        $command = "{$this->getBot()->getPrefix()}{$this->name}";

        if (! empty($this->usage)) {
            $command .= " `{$this->usage}`";
        }

        return $command;
    }

    /**
     * Retrieve the command aliases.
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Retrieve the command description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Retrieve the command cooldown.
     *
     * @return int
     */
    public function getCooldown()
    {
        return $this->cooldown;
    }

    /**
     * Retrieve the command cooldown message.
     *
     * @return string
     */
    public function getCooldownMessage()
    {
        return $this->cooldownMessage;
    }

    /**
     * Retrieve the command usage.
     *
     * @return string
     */
    public function getUsage()
    {
        return $this->usage;
    }

    /**
     * Retrieve the bot instance.
     *
     * @return \App\Bot\Bot
     */
    public function getBot()
    {
        return $this->bot;
    }

    /**
     * Retrieve the console instance.
     *
     * @return \LaravelZero\Framework\Commands\Command
     */
    public function getConsole()
    {
        return $this->console;
    }

    /**
     * Retrieve the Discord instance.
     *
     * @return \Discord\DiscordCommandClient
     */
    public function getDiscord()
    {
        return $this->discord;
    }

    /**
     * Determine if the command requires admin permissions.
     *
     * @return bool
     */
    public function isAdminCommand()
    {
        return $this->admin;
    }

    /**
     * Determine if the command is hidden.
     *
     * @return bool
     */
    public function isHidden()
    {
        return $this->hidden;
    }
}
