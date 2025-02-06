<?php

namespace Laracord\Bot\Concerns;

use Discord\Discord;
use Discord\WebSockets\Intents;
use Laracord\Discord\Message;

trait HasDiscord
{
    /**
     * The Discord instance.
     */
    public ?Discord $discord = null;

    /**
     * The Discord bot name.
     */
    protected string $name = '';

    /**
     * The Discord bot token.
     */
    protected string $token = '';

    /**
     * The Discord shard ID.
     */
    protected ?int $shardId = null;

    /**
     * The Discord shard count.
     */
    protected ?int $shardCount = null;

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
     * Register the Discord instance.
     */
    protected function registerDiscord(): void
    {
        $this->discord = new Discord($this->getOptions());
        $this->admins = config('discord.admins', $this->admins);
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
     * Set the bot token.
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
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
            $this->logger->error('You must provide a Discord bot token.');

            exit(1);
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
     * Set the bot shard ID.
     */
    public function setShard(int $id, int $count): self
    {
        $this->shardId = $id;
        $this->shardCount = $count;

        return $this;
    }

    /**
     * Determine if the bot is a shard.
     */
    public function isShard(): bool
    {
        return filled($this->shardId) && filled($this->shardCount);
    }

    /**
     * Get the current shard ID.
     */
    public function getShardId(): ?int
    {
        return $this->shardId;
    }

    /**
     * Get the shard count.
     */
    public function getShardCount(): ?int
    {
        return $this->shardCount;
    }

    /**
     * Get the bot options.
     */
    public function getOptions(): array
    {
        if ($this->options) {
            return $this->options;
        }

        $options = [
            'token' => $this->getToken(),
            'intents' => $this->getIntents(),
            'logger' => $this->getLogger(),
            'loop' => $this->getLoop(),
        ];

        if ($this->isShard()) {
            $options = [
                ...$options,
                'shardId' => $this->shardId,
                'shardCount' => $this->shardCount,
            ];
        }

        return $this->options = [
            ...config('discord.options', []),
            ...$options,
        ];
    }

    /**
     * Get the Discord admins.
     */
    public function getAdmins(): array
    {
        return $this->admins;
    }

    /**
     * Retrieve the Discord instance.
     */
    public function discord(): ?Discord
    {
        return $this->discord;
    }

    /**
     * Build a mesage for Discord.
     */
    public function message(string $content = ''): Message
    {
        return Message::make($this)
            ->content($content);
    }
}
