<?php

namespace Laracord\Discord;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class Embed
{
    /**
     * The Discord message endpoint.
     */
    protected string $endpoint = 'https://canary.discord.com/api/v10/channels/{channel_id}';

    /**
     * The Discord channel.
     */
    protected string $channel = '';

    /**
     * The Discord Bot token.
     */
    protected ?string $token = null;

    /**
     * The message username.
     */
    protected ?string $username = null;

    /**
     * The message content.
     */
    protected ?string $content = null;

    /**
     * The message avatar URL.
     */
    protected ?string $avatarUrl = null;

    /**
     * The text to speech state.
     */
    protected bool $tts = false;

    /**
     * The message title.
     */
    protected ?string $title = null;

    /**
     * The message description.
     */
    protected ?string $description = null;

    /**
     * The message color.
     */
    protected ?string $color = null;

    /**
     * The message footer icon.
     */
    protected ?string $footerIcon = null;

    /**
     * The message footer text.
     */
    protected ?string $footerText = null;

    /**
     * The message thumbnail URL.
     */
    protected ?string $thumbnailUrl = null;

    /**
     * The message URL.
     */
    protected ?string $url = null;

    /**
     * The message image URL.
     */
    protected ?string $imageUrl = null;

    /**
     * The message timestamp.
     */
    protected ?string $timestamp = null;

    /**
     * The message author name.
     */
    protected ?string $authorName = null;

    /**
     * The message author URL.
     */
    protected ?string $authorUrl = null;

    /**
     * The message author icon.
     */
    protected ?string $authorIcon = null;

    /**
     * The message fields.
     */
    protected array $fields = [];

    /**
     * The message components.
     */
    protected array $components = [];

    /**
     * Create a new Discord embed instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->token = config('discord.token');
    }

    /**
     * Make a new Discord embed instance.
     *
     * @return static
     */
    public static function make(): self
    {
        return new static();
    }

    /**
     * Get the Discord HTTP client.
     */
    protected function client(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => "Bot {$this->getToken()}",
        ])->baseUrl($this->getEndpoint());
    }

    /**
     * Send the message to Discord.
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function send()
    {
        $response = $this->client()->post('messages', $this->toArray());

        return $response;
    }

    /**
     * Get the Discord message endpoint.
     */
    protected function getEndpoint(): string
    {
        return str_replace('{channel_id}', $this->getChannel(), $this->endpoint);
    }

    /**
     * Get the Discord channel.
     */
    protected function getChannel(): string
    {
        if (! $this->channel) {
            throw new Exception('You must specify a Discord channel.');
        }

        return $this->channel;
    }

    /**
     * Get the Discord token.
     */
    protected function getToken(): string
    {
        return $this->token;
    }

    /**
     * Get the components.
     */
    public function getComponents(): array
    {
        if (empty($this->components)) {
            return [];
        }

        return [
            'type' => 1,
            'components' => $this->components,
        ];
    }

    /**
     * Set the channel.
     *
     * @return $this
     */
    public function channel(string $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Set the message username.
     */
    public function username(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set the message content.
     */
    public function content(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set the message avatar URL.
     */
    public function avatarUrl(?string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl;

        return $this;
    }

    /**
     * Set whether the message should be text-to-speech.
     */
    public function tts(bool $tts): self
    {
        $this->tts = $tts;

        return $this;
    }

    /**
     * Set the message title.
     */
    public function title(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the message description.
     */
    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the message color.
     */
    public function color(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Set the message footer icon.
     */
    public function footerIcon(?string $footerIcon): self
    {
        $this->footerIcon = $footerIcon;

        return $this;
    }

    /**
     * Set the message footer text.
     */
    public function footerText(?string $footerText): self
    {
        $this->footerText = $footerText;

        return $this;
    }

    /**
     * Set the message thumbnail URL.
     */
    public function thumbnailUrl(?string $thumbnailUrl): self
    {
        $this->thumbnailUrl = $thumbnailUrl;

        return $this;
    }

    /**
     * Set the message URL.
     */
    public function url(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Set the message image URL.
     */
    public function imageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /**
     * Set the message timestamp.
     */
    public function timestamp(?string $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Set the message author name.
     */
    public function authorName(?string $authorName): self
    {
        $this->authorName = $authorName;

        return $this;
    }

    /**
     * Set the message author URL.
     */
    public function authorUrl(?string $authorUrl): self
    {
        $this->authorUrl = $authorUrl;

        return $this;
    }

    /**
     * Set the message author icon.
     */
    public function authorIcon(?string $authorIcon): self
    {
        $this->authorIcon = $authorIcon;

        return $this;
    }

    /**
     * Set the message fields.
     */
    public function fields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Add a field to the message.
     */
    public function field(string $name, mixed $value, bool $inline = true, bool $condition = false): self
    {
        if ($condition) {
            return $this;
        }

        $this->fields[] = [
            'name' => $name,
            'value' => "{$value}",
            'inline' => $inline,
        ];

        return $this;
    }

    /**
     * Add a code field to the message.
     */
    public function codeField(string $name, string $value, string $language = 'py', bool $condition = false): self
    {
        if ($condition) {
            return $this;
        }

        return $this->field($name, "```{$language}\n{$value}\n```", false);
    }

    /**
     * Set the message components.
     */
    public function components(array $components): self
    {
        $this->components = $components;

        return $this;
    }

    /**
     * Add a URL button to the message.
     */
    public function button(string $label, string $url, mixed $emoji = null): self
    {
        $button = [
            'type' => 2,
            'label' => $label,
            'style' => 5,
            'url' => $url,
        ];

        if ($emoji) {
            $emoji = is_array($emoji) ? array_merge([
                'id' => null,
                'animated' => false,
            ], $emoji) : [
                'name' => $emoji,
                'id' => null,
                'animated' => false,
            ];

            $button['emoji'] = $emoji;
        }

        $this->components[] = $button;

        return $this;
    }

    /**
     * Convert the message to JSON.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Convert the message to an array.
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'tts' => $this->tts,
            'components' => [$this->getComponents()],
            'embeds' => [
                [
                    'title' => $this->title,
                    'description' => $this->description,
                    'url' => $this->url,
                    'timestamp' => $this->timestamp,
                    'color' => $this->color,
                    'footer' => [
                        'text' => $this->footerText,
                        'icon_url' => $this->footerIcon,
                    ],
                    'thumbnail' => [
                        'url' => $this->thumbnailUrl,
                    ],
                    'image' => [
                        'url' => $this->imageUrl,
                    ],
                    'author' => [
                        'name' => $this->authorName,
                        'url' => $this->authorUrl,
                        'icon_url' => $this->authorIcon,
                    ],
                    'fields' => $this->fields,
                ],
            ],
        ];
    }
}
