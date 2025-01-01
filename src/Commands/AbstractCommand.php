<?php

namespace Laracord\Commands;

use Discord\Parts\Guild\Guild;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\User\User;
use Illuminate\Support\Str;
use Laracord\Concerns\HasHandler;
use Laracord\Discord\Concerns\HasModal;
use Laracord\Laracord;

abstract class AbstractCommand
{
    use HasHandler, HasModal;

    /**
     * The bot instance.
     *
     * @var \Laracord\Laracord
     */
    protected $bot;

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
     * The user instance.
     *
     * @var \App\Models\User
     */
    protected $user;

    /**
     * The command name.
     *
     * @var string
     */
    protected $name;

    /**
     * The command description.
     *
     * @var string|null
     */
    protected $description;

    /**
     * The command type.
     */
    protected string|int $type = 'chat';

    /**
     * The guild the command belongs to.
     *
     * @var string
     */
    protected $guild;

    /**
     * Determine whether the command can be used in a direct message.
     */
    protected bool $directMessage = true;

    /**
     * Determines whether the command requires admin permissions.
     *
     * @var bool
     */
    protected $admin = false;

    /**
     * Determines whether the command should be displayed in the commands list.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * Determines whether the command is enabled.
     *
     * @var bool
     */
    protected $enabled = true;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Laracord $bot)
    {
        $this->bot = $bot;
        $this->console = $bot->console();
        $this->discord = $bot->discord();
    }

    /**
     * Make a new command instance.
     */
    public static function make(Laracord $bot): self
    {
        return new static($bot);
    }

    /**
     * The command interaction routes.
     */
    public function interactions(): array
    {
        return [];
    }

    /**
     * Build an embed for use in a Discord message.
     *
     * @param  string  $content
     * @return \Laracord\Discord\Message
     */
    public function message($content = '')
    {
        return $this->bot()->message($content)->routePrefix($this->getName());
    }

    /**
     * Determine if the Discord user is an admin.
     *
     * @param  string|\Discord\Parts\User\User  $user
     * @return bool
     */
    public function isAdmin($user)
    {
        if (! $user instanceof User) {
            $user = $this->discord()->users->get('id', $user);
        }

        if ($this->bot()->getAdmins()) {
            return in_array($user->id, $this->bot()->getAdmins());
        }

        return $this->getUser($user)->is_admin;
    }

    /**
     * Determine if the command can be used in a direct message.
     */
    public function canDirectMessage(): bool
    {
        return $this->directMessage;
    }

    /**
     * Resolve a Discord user.
     */
    public function resolveUser(string $username): ?User
    {
        return ! empty($username) ? $this->discord()->users->filter(function ($user) use ($username) {
            $username = str_replace(['<', '@', '>'], '', strtolower($username));

            return ($user->username === $username || $user->id === $username) && ! $user->bot;
        })->first() : null;
    }

    /**
     * Get the command user.
     *
     * @param  \Discord\Parts\User\User  $user
     * @return \App\Models\User|null
     */
    public function getUser($user)
    {
        $model = Str::start(app()->getNamespace(), '\\').'Models\\User';

        if (! class_exists($model)) {
            throw new Exception('The user model could not be found.');
        }

        return $model::firstOrCreate(['discord_id' => $user->id], [
            'discord_id' => $user->id,
            'username' => $user->username,
        ]) ?? null;
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
     * Retrieve the command signature.
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->getName();
    }

    /**
     * Retrieve the full command syntax.
     *
     * @return string
     */
    public function getSyntax()
    {
        $command = $this->getSignature();

        if (! empty($this->usage)) {
            $command .= " `{$this->usage}`";
        }

        return $command;
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
     * Get the command type.
     */
    public function getType(): int
    {
        return match ($this->type) {
            default => Command::CHAT_INPUT,
        };
    }

    /**
     * Retrieve the command guild.
     */
    public function getGuild(): ?string
    {
        return $this->guild ?? null;
    }

    /**
     * Retrieve the bot instance.
     *
     * @return \Laracord\Laracord
     */
    public function bot()
    {
        return $this->bot;
    }

    /**
     * Retrieve the console instance.
     *
     * @return \Laracord\Console\Commands\Command
     */
    public function console()
    {
        return $this->console;
    }

    /**
     * Retrieve the Discord instance.
     *
     * @return \Discord\DiscordCommandClient
     */
    public function discord()
    {
        return $this->discord;
    }

    /**
     * Determine if the command requires admin permissions.
     */
    public function isAdminCommand(): bool
    {
        return $this->admin;
    }

    /**
     * Determine if the command is hidden.
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Determine if the command is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
