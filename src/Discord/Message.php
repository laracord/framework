<?php

namespace Laracord\Discord;

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message as ChannelMessage;
use Discord\Parts\User\User;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laracord\Laracord;
use React\Promise\ExtendedPromiseInterface;
use Throwable;

class Message
{
    /**
     * The bot instance.
     */
    protected ?Laracord $bot = null;

    /**
     * The message channel.
     */
    protected ?Channel $channel = null;

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
     * The message files.
     */
    protected array $files = [];

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
    public function __construct(?Laracord $bot)
    {
        $this->bot = $bot ?: app('bot');

        $this
            ->authorName($this->bot->discord()->user->username)
            ->authorIcon($this->bot->discord()->user->avatar)
            ->success();
    }

    /**
     * Make a new Discord message instance.
     */
    public static function make(?Laracord $bot): self
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

        if ($this->files) {
            foreach ($this->files as $file) {
                $message->addFileFromContent($file['filename'], $file['content']);
            }
        }

        return $message;
    }

    /**
     * Send the message.
     */
    public function send(mixed $destination = null): ?ExtendedPromiseInterface
    {
        if ($destination) {
            $this->channel($destination);
        }

        return $this->getChannel()->sendMessage($this->build());
    }

    /**
     * Send the message to a user.
     */
    public function sendTo(mixed $user): ?ExtendedPromiseInterface
    {
        if (is_numeric($user)) {
            $member = $this->bot->discord()->users->get('id', $user);

            if (! $member) {
                $this->bot->console()->error("Could not find user <fg=red>{$user}</> to send message");

                return null;
            }

            $user = $member;
        }

        if ($user instanceof ChannelMessage) {
            $user = $user->author;
        }

        if (! $user instanceof User) {
            $this->bot->console()->error('You must provide a valid Discord user.');

            return null;
        }

        return $user->sendMessage($this->build());
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
     * Get the message channel.
     */
    public function getChannel(): Channel
    {
        if (! $this->channel) {
            throw new Exception('You must provide a Discord channel.');
        }

        return $this->channel;
    }

    /**
     * Set the message channel.
     */
    public function channel(mixed $channel): self
    {
        if (is_numeric($channel)) {
            $channel = $this->bot->discord()->getChannel($channel);
        }

        if ($channel instanceof ChannelMessage) {
            $channel = $channel->channel;
        }

        if (! $channel instanceof Channel) {
            throw new Exception('You must provide a valid Discord channel.');
        }

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
     * Set the message body.
     */
    public function body(string $body = ''): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Add a file from content to the message.
     */
    public function file(string $content = '', string $filename = ''): self
    {
        $filename = $filename ?? 'file.txt';

        $this->files[] = [
            'content' => $content,
            'filename' => $filename,
        ];

        return $this;
    }

    /**
     * Add a file to the message.
     */
    public function filePath(string $path, string $filename = ''): self
    {
        if (! file_exists($path)) {
            $this->bot->console()->error("File <fg=red>{$path}</> does not exist");

            return $this;
        }

        $filename = $filename ?? basename($path);

        $this->file(file_get_contents($path), $filename);

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
    public function timestamp(mixed $timestamp = null): self
    {
        if (! $timestamp) {
            $timestamp = now();
        }

        if ($timestamp instanceof Carbon) {
            $timestamp = $timestamp->toIso8601String();
        }

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
        foreach ($fields as $key => $value) {
            $this->field($key, $value);
        }

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
     * Clear the fields from the message.
     */
    public function clearFields(): self
    {
        $this->fields = [];

        return $this;
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
    public function button(string $label, mixed $value, mixed $emoji = null, ?string $style = null, array $options = []): self
    {
        $style = match ($style) {
            'link' => Button::STYLE_LINK,
            'primary' => Button::STYLE_PRIMARY,
            'secondary' => Button::STYLE_SECONDARY,
            'success' => Button::STYLE_SUCCESS,
            'danger' => Button::STYLE_DANGER,
            default => $style,
        };

        $style = $style ?? (is_string($value) ? Button::STYLE_LINK : Button::STYLE_PRIMARY);

        $button = Button::new($style)
            ->setLabel($label)
            ->setEmoji($emoji);

        if ($options) {
            foreach ($options as $key => $option) {
                $key = Str::of($key)->camel()->ucfirst()->__toString();

                try {
                    $button = $button->{$key}($option);
                } catch (Throwable) {
                    $this->bot->console()->error("Invalid button option <fg=red>{$key}</>");

                    continue;
                }
            }
        }

        $button = match ($style) {
            Button::STYLE_LINK => $button->setUrl($value),
            default => $button->setListener($value, $this->bot->discord()),
        };

        $this->buttons[] = $button;

        return $this;
    }

    /**
     * Add buttons to the message.
     */
    public function buttons(array $buttons): self
    {
        foreach ($buttons as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $value = [$key, $value];
            }

            $this->button(...$value);
        }

        return $this;
    }

    /**
     * Clear the buttons from the message.
     */
    public function clearButtons(): self
    {
        $this->buttons = [];

        return $this;
    }
}
