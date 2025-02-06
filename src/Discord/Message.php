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
use Discord\Parts\Channel\Poll\Poll;
use Discord\Parts\Channel\Webhook;
use Discord\Parts\Guild\Sticker;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\User;
use Discord\Repository\Channel\WebhookRepository;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laracord\Laracord;
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
     * The message poll.
     */
    protected ?Poll $poll = null;

    /**
     * The message attachments.
     */
    protected ?Collection $attachments = null;

    /**
     * The message stickers.
     */
    protected array $stickers = [];

    /**
     * The message webhook.
     */
    protected string|bool $webhook = false;

    /**
     * The additional message embeds.
     */
    protected array $embeds = [];

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
            ->setAvatarUrl($this->getAttachment($this->avatarUrl))
            ->setTts($this->tts)
            ->setContent($this->body)
            ->setStickers($this->stickers)
            ->setComponents($this->getComponents());

        if ($this->hasContent() || $this->hasFields()) {
            $message->addEmbed($this->getEmbed());
        }

        if ($this->hasEmbeds()) {
            foreach ($this->embeds as $embed) {
                $message->addEmbed($embed);
            }
        }

        if ($this->hasSelects()) {
            foreach ($this->selects as $select) {
                $message->addComponent($select);
            }
        }

        if ($this->hasButtons()) {
            foreach ($this->getButtons() as $button) {
                $message->addComponent($button);
            }
        }

        if ($this->hasPoll()) {
            $message->setPoll($this->poll);
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
    public function send(mixed $destination = null): ?PromiseInterface
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
    public function sendTo(mixed $user): ?PromiseInterface
    {
        if (is_numeric($user)) {
            $member = $this->bot->discord()->users->get('id', $user);

            if (! $member) {
                $this->bot->logger->error("Could not find user <fg=red>{$user}</> to send message");

                return null;
            }

            $user = $member;
        }

        if ($user instanceof ChannelMessage) {
            $user = $user->author;
        }

        if (! $user instanceof User) {
            $this->bot->logger->error('You must provide a valid Discord user.');

            return null;
        }

        return $user->sendMessage($this->build());
    }

    /**
     * Send the message as a webhook.
     */
    protected function handleWebhook(): ?PromiseInterface
    {
        try {
            /** @var WebhookRepository $webhooks */
            $webhooks = await($this->getChannel()->webhooks->freshen());
        } catch (NoPermissionsException) {
            $this->bot->logger->error("\nMissing permission to fetch channel webhooks.");

            return null;
        }

        if (! $webhooks) {
            $this->bot->logger->error('Failed to fetch channel webhooks.');

            return null;
        }

        if ($this->webhook === true) {
            $webhook = $webhooks->find(fn (Webhook $webhook) => $webhook->name === $this->bot->discord()->username);

            if (! $webhook) {
                return $webhooks->save(new Webhook($this->bot->discord(), [
                    'name' => $this->bot->discord()->username,
                ]))->then(
                    fn (Webhook $webhook) => $webhook->execute($this->build()),
                    fn () => $this->bot->logger->error('Failed to create message webhook.')
                );
            }

            return $webhook->execute($this->build());
        }

        $webhook = $this->getChannel()->webhooks->get('url', $this->webhook);

        if (! $webhook) {
            $this->bot->logger->error("Could not find webhook <fg=red>{$this->webhook}</> on channel to send message.");

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
    public function reply(Interaction|ChannelMessage $message, bool $ephemeral = false): PromiseInterface
    {
        if ($message instanceof Interaction) {
            return $message->respondWithMessage($this->build(), ephemeral: $ephemeral);
        }

        return $message->reply($this->build());
    }

    /**
     * Edit an existing message or interaction message.
     */
    public function edit(Interaction|ChannelMessage $message): PromiseInterface
    {
        if ($message instanceof Interaction) {
            return $message->updateMessage($this->build());
        }

        return $message->edit($this->build());
    }

    /**
     * Edit an existing message if it is owned by the bot, otherwise replying instead.
     */
    public function editOrReply(Interaction|ChannelMessage $message, bool $ephemeral = false): PromiseInterface
    {
        if ($message instanceof Interaction) {
            return $message->message?->user_id === $this->bot->discord()->id
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
                'icon_url' => $this->getAttachment($this->footerIcon),
            ],
            'thumbnail' => [
                'url' => $this->getAttachment($this->thumbnailUrl),
            ],
            'image' => [
                'url' => $this->getAttachment($this->imageUrl),
            ],
            'author' => [
                'name' => $this->authorName,
                'url' => $this->authorUrl,
                'icon_url' => $this->getAttachment($this->authorIcon),
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
     * Get the button components.
     */
    public function getButtons(): array
    {
        if (! $this->hasButtons()) {
            return [];
        }

        return collect($this->buttons)->chunk(5)->map(function ($buttons) {
            $row = ActionRow::new();

            foreach ($buttons as $button) {
                $row->addComponent($button);
            }

            return $row;
        })->all();
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
     * Add a file using raw input, local storage, or a remote URL to the message.
     */
    public function file(string $input = '', ?string $filename = null): self
    {
        $isPath = (! $isUrl = Str::isUrl($input))
            && Str::length($input) <= 1024
            && ! Str::contains($input, [DIRECTORY_SEPARATOR, "\n"])
            && Str::isMatch('/\.\w+$/', $input);

        $isPath = $isPath && Storage::drive('local')->exists($input);

        $content = match (true) {
            $isUrl => file_get_contents($input),
            $isPath => Storage::drive('local')->get($input),
            default => $input,
        };

        $filename = match (true) {
            filled($filename) => $filename,
            $isUrl => basename(parse_url($input, PHP_URL_PATH)),
            $isPath => basename($input),
            default => 'file.txt',
        };

        $this->files[] = [
            'content' => $content,
            'filename' => $filename,
        ];

        return $this;
    }

    /**
     * Add a file to the message.
     */
    public function filePath(string $path, ?string $filename = null): self
    {
        if (! file_exists($path)) {
            $this->bot->logger->error("File <fg=red>{$path}</> does not exist");

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
     * Retrieve the message file attachments.
     */
    protected function getAttachments(): Collection
    {
        return $this->attachments ??= collect($this->files)->mapWithKeys(fn ($file) => [
            $file['filename'] => "attachment://{$file['filename']}",
        ]);
    }

    /**
     * Retrieve a message file attachment.
     */
    protected function getAttachment(?string $filename = null): ?string
    {
        if (blank($filename)) {
            return null;
        }

        return $this->getAttachments()->get($filename, $filename);
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
        return $this->thumbnailUrl($thumbnailUrl);
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
        return $this->imageUrl($imageUrl);
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
     * Add a sticker to the message.
     */
    public function sticker(string|Sticker $sticker): self
    {
        $this->stickers[] = $sticker instanceof Sticker
            ? $sticker->id
            : $sticker;

        return $this;
    }

    /**
     * Add stickers to the message.
     */
    public function stickers(array $stickers): self
    {
        foreach ($stickers as $sticker) {
            $this->sticker($sticker);
        }

        return $this;
    }

    /**
     * Clear the stickers from the message.
     */
    public function clearStickers(): self
    {
        $this->stickers = [];

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
    public function fields(array|Arrayable|Collection $fields, bool $inline = true): self
    {
        $fields = match (true) {
            $fields instanceof Collection => $fields->all(),
            $fields instanceof Arrayable => $fields->toArray(),
            default => $fields
        };

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
            'value' => (string) $value,
            'inline' => $inline,
        ];

        return $this;
    }

    /**
     * Add a code field to the message.
     */
    public function codeField(string $name, string $value, string $language = 'php', bool $condition = false): self
    {
        if ($condition) {
            return $this;
        }

        return $this->field($name, "```{$language}\n{$value}\n```", inline: false);
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
        bool $hidden = false,
        int $minValues = 1,
        int $maxValues = 1,
        ?string $type = null,
        ?string $route = null,
        ?array $options = []
    ): self {
        if ($hidden) {
            return $this;
        }

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
                    $this->bot->logger->error("Invalid select menu option <fg=red>{$key}</>");

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
        bool $hidden = false,
        ?string $id = null,
        ?string $route = null,
        array $options = []
    ): self {
        if ($hidden) {
            return $this;
        }

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
                    $this->bot->logger->error("Invalid button option <fg=red>{$key}</>");

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
     * Add a poll to the message.
     */
    public function poll(string $question, array $answers, int $duration = 24, bool $multiselect = false): self
    {
        $answers = collect($answers)
            ->map(fn ($value, $key) => is_string($key)
                ? ['emoji' => $key, 'text' => $value]
                : ['text' => $value]
            )->all();

        $this->poll = (new Poll($this->bot->discord()))
            ->setQuestion($question)
            ->setAnswers($answers)
            ->setDuration($duration)
            ->setAllowMultiselect($multiselect);

        return $this;
    }

    /**
     * Clear the poll from the message.
     */
    public function clearPoll(): self
    {
        $this->poll = null;

        return $this;
    }

    /**
     * Determine if the message has a poll.
     */
    public function hasPoll(): bool
    {
        return ! is_null($this->poll);
    }

    /**
     * Add an additional embed to the message.
     */
    public function withEmbed(MessageBuilder|self $builder): self
    {
        if (count($this->embeds) === 10) {
            throw new Exception('Messages cannot exceed 10 embeds.');
        }

        if ($builder instanceof self) {
            $builder = $builder->build();
        }

        $embeds = $builder->getEmbeds();

        if (! $embeds) {
            throw new Exception('Builder must contain at least one embed.');
        }

        $this->embeds[] = $embeds[0];

        return $this;
    }

    /**
     * Add additional embeds to the message.
     */
    public function withEmbeds(array $builders): self
    {
        foreach ($builders as $builder) {
            $this->withEmbed($builder);
        }

        return $this;
    }

    /**
     * Determine if the message has additional embeds.
     */
    public function hasEmbeds(): bool
    {
        return ! empty($this->embeds);
    }

    /**
     * Clear the additional embeds from the message.
     */
    public function clearEmbeds(): self
    {
        $this->embeds = [];

        return $this;
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
