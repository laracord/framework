<?php

namespace Laracord\Discord;

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\ChannelSelect;
use Discord\Builders\Components\MentionableSelect;
use Discord\Builders\Components\Option;
use Discord\Builders\Components\RoleSelect;
use Discord\Builders\Components\StringSelect;
use Discord\Builders\Components\UserSelect;
use Discord\Builders\MessageBuilder;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message as ChannelMessage;
use Discord\Parts\Channel\Webhook;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\User;
use Discord\Repository\Channel\WebhookRepository;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laracord\Laracord;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\PromiseInterface;
use Throwable;

use function React\Async\await;

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
     * The message select menus.
     */
    protected array $selects = [];

    /**
     * The message files.
     */
    protected array $files = [];

    /**
     * The message webhook.
     */
    protected string|bool $webhook = false;

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
     * The interaction route prefix.
     */
    protected ?string $routePrefix = null;

    /**
     * Create a new Discord message instance.
     *
     * @return void
     */
    public function __construct(?Laracord $bot)
    {
        $this->bot = $bot ?: app('bot');

        $this
            ->username($username = $this->bot->discord()->username)
            ->avatar($avatar = $this->bot->discord()->avatar)
            ->authorName($username)
            ->authorIcon($avatar)
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
            ->setUsername($this->username)
            ->setAvatarUrl($this->avatarUrl)
            ->setTts($this->tts)
            ->setContent($this->body)
            ->setComponents($this->getComponents());

        if ($this->hasContent() || $this->hasFields()) {
            $message->addEmbed($this->getEmbed());
        }

        if ($this->hasSelects()) {
            foreach ($this->selects as $select) {
                $message->addComponent($select);
            }
        }

        if ($this->hasButtons()) {
            $message->addComponent($this->getButtons());
        }

        if ($this->hasFiles()) {
            foreach ($this->files as $file) {
                $message->addFileFromContent($file['filename'], $file['content']);
            }
        }

        return $message;
    }

    /**
     * Send the message.
     */
    public function send(mixed $destination = null): PromiseInterface|ExtendedPromiseInterface|null
    {
        if ($destination) {
            $this->channel($destination);
        }

        if ($this->webhook) {
            return $this->handleWebhook();
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
     * Send the message as a webhook.
     */
    protected function handleWebhook(): ?ExtendedPromiseInterface
    {
        try {
            /** @var WebhookRepository $webhooks */
            $webhooks = await($this->getChannel()->webhooks->freshen());
        } catch (NoPermissionsException) {
            $this->bot->console()->error("\nMissing permission to fetch channel webhooks.");

            return null;
        }

        if (! $webhooks) {
            $this->bot->console()->error('Failed to fetch channel webhooks.');

            return null;
        }

        if ($this->webhook === true) {
            $webhook = $webhooks->find(fn (Webhook $webhook) => $webhook->name === $this->bot->discord()->username);

            if (! $webhook) {
                return $webhooks->save(new Webhook($this->bot->discord(), [
                    'name' => $this->bot->discord()->username,
                ]))->then(
                    fn (Webhook $webhook) => $webhook->execute($this->build()),
                    fn () => $this->bot->console()->error('Failed to create message webhook.')
                );
            }

            return $webhook->execute($this->build());
        }

        $webhook = $this->getChannel()->webhooks->get('url', $this->webhook);

        if (! $webhook) {
            $this->bot->console()->error("Could not find webhook <fg=red>{$this->webhook}</> on channel to send message.");

            return null;
        }

        return $webhook->execute($this->build());
    }

    /**
     * Send the message as a webhook.
     */
    public function webhook(string|bool $value = true): self
    {
        $this->webhook = $value;

        return $this;
    }

    /**
     * Reply to a message or interaction.
     */
    public function reply(Interaction|ChannelMessage $message, bool $ephemeral = false): ExtendedPromiseInterface
    {
        if ($message instanceof Interaction) {
            return $message->respondWithMessage($this->build(), ephemeral: $ephemeral);
        }

        return $message->reply($this->build());
    }

    /**
     * Edit an existing message or interaction message.
     */
    public function edit(Interaction|ChannelMessage $message): ExtendedPromiseInterface
    {
        if ($message instanceof Interaction) {
            return $message->updateMessage($this->build());
        }

        return $message->edit($this->build());
    }

    /**
     * Edit an existing message if it is owned by the bot, otherwise replying instead.
     */
    public function editOrReply(Interaction|ChannelMessage $message, bool $ephemeral = false): ExtendedPromiseInterface
    {
        if ($message instanceof Interaction) {
            return $message->user->id === $this->bot->discord()->id
                ? $this->edit($message)
                : $this->reply($message, $ephemeral);
        }

        return $message->author->id === $this->bot->discord()->id
            ? $this->edit($message)
            : $this->reply($message);
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
        if (! $this->hasButtons()) {
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
     * Determine if the message has content.
     */
    public function hasContent(): bool
    {
        return ! empty($this->content);
    }

    /**
     * Set the message avatar.
     */
    public function avatar(?string $avatarUrl): self
    {
        return $this->avatarUrl($avatarUrl);
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
     * Determine if the message has files.
     */
    public function hasFiles(): bool
    {
        return ! empty($this->files);
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

        if (str_starts_with($color, '#')) {
            $color = hexdec(
                Str::of($color)->replace('#', '')->limit(6, '')->toString()
            );
        }

        $this->color = (string) $color;

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
    public function thumbnail(?string $thumbnailUrl): self
    {
        $this->thumbnailUrl = $thumbnailUrl;

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
    public function image(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

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
     * Clear the message author.
     */
    public function clearAuthor(): self
    {
        return $this->authorName('')->authorIcon('');
    }

    /**
     * Set the message fields.
     */
    public function fields(array $fields, bool $inline = true): self
    {
        foreach ($fields as $key => $value) {
            $this->field($key, $value, $inline);
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
     * Determine if the message has fields.
     */
    public function hasFields(): bool
    {
        return ! empty($this->fields);
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
     * Add a select menu to the message.
     */
    public function select(
        array $items = [],
        ?callable $listener = null,
        ?string $placeholder = null,
        ?string $id = null,
        bool $disabled = false,
        int $minValues = 1,
        int $maxValues = 1,
        ?string $type = null,
        ?string $route = null,
        ?array $options = []
    ): self {
        $select = match ($type) {
            'channel' => ChannelSelect::new(),
            'mentionable' => MentionableSelect::new(),
            'role' => RoleSelect::new(),
            'user' => UserSelect::new(),
            default => StringSelect::new(),
        };

        $select = $select
            ->setPlaceholder($placeholder)
            ->setMinValues($minValues)
            ->setMaxValues($maxValues)
            ->setDisabled($disabled);

        if ($id) {
            $select = $select->setCustomId($id);
        }

        if ($route) {
            $select = $this->getRoutePrefix()
                ? $select->setCustomId("{$this->getRoutePrefix()}@{$route}")
                : $select->setCustomId($route);
        }

        if ($listener) {
            $select = $select->setListener($listener, $this->bot->discord());
        }

        if ($options) {
            foreach ($options as $key => $option) {
                $key = Str::of($key)->camel()->ucfirst()->start('set')->toString();

                try {
                    $select = $select->{$key}($option);
                } catch (Throwable) {
                    $this->bot->console()->error("Invalid select menu option <fg=red>{$key}</>");

                    continue;
                }
            }
        }

        foreach ($items as $key => $value) {
            if (! is_array($value)) {
                $select->addOption(
                    Option::new(is_int($key) ? $value : $key, $value)
                );

                continue;
            }

            $option = Option::new($value['label'] ?? $key, $value['value'] ?? $key)
                ->setDescription($value['description'] ?? null)
                ->setEmoji($value['emoji'] ?? null)
                ->setDefault($value['default'] ?? false);

            $select->addOption($option);
        }

        $this->selects[] = $select;

        return $this;
    }

    /**
     * Clear the select menus from the message.
     */
    public function clearSelects(): self
    {
        $this->selects = [];

        return $this;
    }

    /**
     * Determine if the message has select menus.
     */
    public function hasSelects(): bool
    {
        return ! empty($this->selects);
    }

    /**
     * Add a button to the message.
     */
    public function button(
        string $label,
        mixed $value = null,
        mixed $emoji = null,
        ?string $style = null,
        bool $disabled = false,
        ?string $id = null,
        ?string $route = null,
        array $options = []
    ): self {
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
            ->setEmoji($emoji)
            ->setDisabled($disabled);

        if ($id) {
            $button = $button->setCustomId($id);
        }

        if ($route) {
            $button = $this->getRoutePrefix()
                ? $button->setCustomId("{$this->getRoutePrefix()}@{$route}")
                : $button->setCustomId($route);
        }

        if ($options) {
            foreach ($options as $key => $option) {
                $key = Str::of($key)->camel()->ucfirst()->start('set')->toString();

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
            default => $value ? $button->setListener($value, $this->bot->discord()) : $button,
        };

        if (! $value && ! $route && ! $id) {
            throw new Exception('Message buttons must contain a valid `value`, `route`, or `id`.');

            return $this;
        }

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

    /**
     * Determine if the message has buttons.
     */
    public function hasButtons(): bool
    {
        return ! empty($this->buttons);
    }

    /**
     * Set the interaction route prefix.
     */
    public function routePrefix(?string $routePrefix): self
    {
        $this->routePrefix = Str::slug($routePrefix);

        return $this;
    }

    /**
     * Retrieve the interaction route prefix.
     */
    public function getRoutePrefix(): ?string
    {
        return $this->routePrefix;
    }
}
