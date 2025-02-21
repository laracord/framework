<?php

namespace Laracord\Commands;

use Discord\Parts\Guild\Guild;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\User\User;
use Laracord\Concerns\HasHandler;
use Laracord\Discord\Concerns\HasModal;
use Laracord\HasLaracord;

abstract class AbstractCommand
{
    use HasHandler, HasLaracord, HasModal;

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
     * The command cooldown in seconds.
     *
     * @var int
     */
    protected $cooldown = 0;

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
     * The command cooldown cache.
     */
    protected array $cooldowns = [];

    /**
     * The middleware to be applied to the command.
     */
    protected array $middleware = [];

    /**
     * Get the middleware for the command.
     */
    public function getMiddleware(): array
    {
        return $this->bot()->resolveCommandMiddleware($this->middleware);
    }

    /**
     * Make a new command instance.
     */
    public static function make(): self
    {
        return new static;
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
        return $this->bot->message($content)->routePrefix($this->getName());
    }

    /**
     * Determine if the Discord user is an admin.
     */
    public function isAdmin(User|string $user): bool
    {
        if (! $user instanceof User) {
            $user = $this->discord->users->get('id', $user);
        }

        if ($this->bot->getAdmins()) {
            return in_array($user->id, $this->bot->getAdmins());
        }

        if (! $this->bot->getUserModel()) {
            return false;
        }

        return $this->bot->getUserModel()::where(['discord_id' => $user->id])->first()?->is_admin ?? false;
    }

    /**
     * Determine if the command can be used in a direct message.
     */
    public function canDirectMessage(): bool
    {
        return $this->directMessage;
    }

    /**
     * Retrieve the command name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retrieve the command signature.
     */
    public function getSignature(): string
    {
        return $this->getName();
    }

    /**
     * Retrieve the full command syntax.
     */
    public function getSyntax(): string
    {
        $command = $this->getSignature();

        if (filled($this->usage)) {
            $command .= " `{$this->usage}`";
        }

        return $command;
    }

    /**
     * Retrieve the command description.
     */
    public function getDescription(): string
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
     * Determine if the command requires admin permissions.
     */
    public function isAdminCommand(): bool
    {
        return $this->admin;
    }

    /**
     * Determine if the user is on cooldown.
     */
    public function isOnCooldown(User $user, Guild $guild): bool
    {
        if ($this->getCooldown() === 0) {
            return false;
        }

        $key = "{$user->id}.{$guild->id}";

        if (! isset($this->cooldowns[$key])) {
            $this->cooldowns[$key] = time();

            return false;
        }

        if (time() - $this->cooldowns[$key] < $this->cooldown) {
            return true;
        }

        $this->cooldowns[$key] = time();

        return false;
    }

    /**
     * Retrieve the command cooldown.
     */
    public function getCooldown(): int
    {
        return $this->cooldown;
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
