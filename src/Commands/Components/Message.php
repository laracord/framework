<?php

namespace Laracord\Commands\Components;

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Message as ChannelMessage;
use Laracord\Laracord;

class Message
{
    /**
     * The message username.
     */
    protected ?string $username = null;

    /**
     * The message body.
     */
    protected string $body = '';

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
     * The message content.
     */
    protected ?string $content = null;

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
     * The message buttons.
     */
    protected array $buttons = [];

    /**
     * The default embed colors.
     */
    protected array $colors = [
        'default' => 3066993,
        'success' => 3066993,
        'error' => 15158332,
        'warning' => 15105570,
        'info' => 3447003,
    ];

    /**
     * Create a new Discord message instance.
     *
     * @return void
     */
    public function __construct(Laracord $bot)
    {
        $this->bot = $bot;

        $this
            ->authorName($this->bot->discord()->user->username)
            ->authorIcon($this->bot->discord()->user->avatar)
            ->success();
    }

    /**
     * Make a new Discord message instance.
     */
    public static function make(Laracord $bot): self
    {
        return new static($bot);
    }

    /**
     * Build the message.
     */
    public function build(): MessageBuilder
    {
        $message = MessageBuilder::new()
            ->setTts($this->tts)
            ->setContent($this->body)
            ->setComponents($this->getComponents());

        if ($this->content || $this->fields) {
            $message->addEmbed($this->getEmbed());
        }

        if ($this->buttons) {
            $message->addComponent($this->getButtons());
        }

        return $message;
    }

    /**
     * Send the message.
     */
    public function send(ChannelMessage $message): void
    {
        $message->channel->sendMessage($this->build());
    }

    /**
     * Get the embed.
     */
    public function getEmbed(): array
    {
        return collect([
            'title' => $this->title,
            'description' => $this->content,
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
        ])->filter()->all();
    }

    /**
     * Get the components.
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * Get the buttons.
     */
    public function getButtons()
    {
        if (empty($this->buttons)) {
            return;
        }

        $buttons = ActionRow::new();

        foreach ($this->buttons as $button) {
            $buttons->addComponent($button);
        }

        return $buttons;
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
     * Set the message body.
     */
    public function body(string $body = ''): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Set the message color.
     */
    public function color(string $color): self
    {
        $color = match ($color) {
            'success' => $this->colors['success'],
            'error' => $this->colors['error'],
            'warning' => $this->colors['warning'],
            'info' => $this->colors['info'],
            default => $color,
        };

        $this->color = $color;

        return $this;
    }

    /**
     * Set the message color to success.
     */
    public function success(): self
    {
        return $this->color('success');
    }

    /**
     * Set the message color to error.
     */
    public function error(): self
    {
        return $this->color('error');
    }

    /**
     * Set the message color to warning.
     */
    public function warning(): self
    {
        return $this->color('warning');
    }

    /**
     * Set the message color to info.
     */
    public function info(): self
    {
        return $this->color('info');
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
        $button = Button::new(Button::STYLE_LINK)
            ->setLabel($label)
            ->setUrl($url)
            ->setEmoji($emoji);

        $this->buttons[] = $button;

        return $this;
    }
}
