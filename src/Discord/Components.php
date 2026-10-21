<?php

namespace Laracord\Discord;

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\ChannelSelect;
use Discord\Builders\Components\Container;
use Discord\Builders\Components\File;
use Discord\Builders\Components\MediaGallery;
use Discord\Builders\Components\MentionableSelect;
use Discord\Builders\Components\Option;
use Discord\Builders\Components\RoleSelect;
use Discord\Builders\Components\Section;
use Discord\Builders\Components\Separator;
use Discord\Builders\Components\StringSelect;
use Discord\Builders\Components\TextDisplay;
use Discord\Builders\Components\Thumbnail;
use Discord\Builders\Components\UserSelect;
use Exception;
use Illuminate\Support\Str;
use Laracord\Laracord;
use Throwable;

class Components
{
    /**
     * The bot instance.
     */
    protected ?Laracord $bot = null;

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
     * The current container being built.
     */
    protected ?Container $currentContainer = null;

    /**
     * The current section being built.
     */
    protected ?Section $currentSection = null;

    /**
     * The components being built.
     */
    protected array $components = [];

    /**
     * The interaction route prefix.
     */
    protected ?string $routePrefix = null;

    /**
     * The current action row being built.
     */
    protected ?ActionRow $currentActionRow = null;

    /**
     * The buttons in the current action row.
     */
    protected array $rowButtons = [];

    /**
     * Create a new components instance.
     */
    public function __construct(?Laracord $bot = null)
    {
        $this->bot = $bot ?: app('bot');
    }

    /**
     * Make a new components instance.
     */
    public static function make(?Laracord $bot = null): self
    {
        return new static($bot);
    }

    /**
     * Start a new container.
     */
    public function container(string|int|null $accentColor = null, bool $spoiler = false): self
    {
        $this->currentContainer = Container::new();

        if ($accentColor) {
            $this->currentContainer->setAccentColor($this->parseColor($accentColor));
        }

        if ($spoiler) {
            $this->currentContainer->setSpoiler();
        }

        $this->components[] = $this->currentContainer;

        return $this;
    }

    /**
     * Parse a color value into its integer representation.
     */
    protected function parseColor(int|string $color): int
    {
        if (is_int($color)) {
            return $color;
        }

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

        return (int) $color;
    }

    /**
     * Set the current container's color to success.
     */
    public function success(): self
    {
        $this->ensureContainer();

        $this->currentContainer->setAccentColor($this->colors['success']);

        return $this;
    }

    /**
     * Set the current container's color to error.
     */
    public function error(): self
    {
        $this->ensureContainer();

        $this->currentContainer->setAccentColor($this->colors['error']);

        return $this;
    }

    /**
     * Set the current container's color to warning.
     */
    public function warning(): self
    {
        $this->ensureContainer();

        $this->currentContainer->setAccentColor($this->colors['warning']);

        return $this;
    }

    /**
     * Set the current container's color to info.
     */
    public function info(): self
    {
        $this->ensureContainer();

        $this->currentContainer->setAccentColor($this->colors['info']);

        return $this;
    }

    /**
     * Set the current container's color.
     */
    public function color(int|string $color): self
    {
        $this->ensureContainer();

        $this->currentContainer->setAccentColor($this->parseColor($color));

        return $this;
    }

    /**
     * Get or create the current container.
     */
    protected function ensureContainer(): void
    {
        if (! $this->currentContainer) {
            $this->container();
        }
    }

    /**
     * Add a text display to the current container or section.
     */
    public function text(string|array $text): self
    {
        $this->ensureContainer();

        $text = is_string($text)
            ? [$text]
            : $text;

        $texts = array_slice($text, 0, 3);

        foreach ($texts as $content) {
            $textDisplay = TextDisplay::new($content);

            $this->currentSection
                ? $this->currentSection->addComponent($textDisplay)
                : $this->currentContainer->addComponent($textDisplay);
        }

        return $this;
    }

    /**
     * Add a thumbnail to the current section.
     */
    public function thumbnail(string $url, ?string $description = null, bool $spoiler = false): self
    {
        if (! $this->currentSection) {
            throw new Exception('You must create a section before adding a thumbnail.');
        }

        $thumbnail = Thumbnail::new($url);

        if ($description) {
            $thumbnail->setDescription($description);
        }

        if ($spoiler) {
            $thumbnail->setSpoiler();
        }

        $this->currentSection->setAccessory($thumbnail);
        $this->currentSection = null;

        return $this;
    }

    /**
     * Add a button to the current section or action row.
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

        $button = $this->createButton($label, $value, $emoji, $style, $disabled, $hidden, $id, $route, $options);

        if (! $button) {
            return $this;
        }

        if ($this->currentSection) {
            $this->currentSection->setAccessory($button);
            $this->currentSection = null;

            return $this;
        }

        if (! $this->currentActionRow || count($this->rowButtons) >= 5) {
            $this->row();
        }

        $this->rowButtons[] = $button;
        $this->currentActionRow->addComponent($button);

        return $this;
    }

    /**
     * Add multiple buttons.
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
     * Add a new section.
     */
    public function section(): self
    {
        $this->ensureContainer();

        $this->currentSection = Section::new();

        $this->currentContainer->addComponent($this->currentSection);

        return $this;
    }

    /**
     * Close the current section.
     */
    public function endSection(): self
    {
        $this->currentSection = null;

        return $this;
    }

    /**
     * Add a custom accessory to the current section.
     */
    public function accessory(mixed $accessory): self
    {
        if (! $this->currentSection) {
            throw new Exception('You must create a section before adding an accessory.');
        }

        $this->currentSection->setAccessory($accessory);
        $this->currentSection = null;

        return $this;
    }

    /**
     * Add a separator to the current container.
     */
    public function separator(bool $divider = true, bool $large = false): self
    {
        $this->ensureContainer();

        $separator = Separator::new()
            ->setDivider($divider)
            ->setSpacing($large ? Separator::SPACING_LARGE : Separator::SPACING_SMALL);

        $this->currentContainer->addComponent($separator);

        return $this;
    }

    /**
     * Add a file to the current container.
     */
    public function file(string $filename, bool $spoiler = false): self
    {
        $this->ensureContainer();

        $file = File::new($filename);

        if ($spoiler) {
            $file->setSpoiler();
        }

        $this->currentContainer->addComponent($file);

        return $this;
    }

    /**
     * Add a media gallery to the current container.
     */
    public function gallery(array $items): self
    {
        $this->ensureContainer();

        $gallery = MediaGallery::new();

        $this->currentContainer->addComponent($gallery);

        foreach ($items as $item) {
            $gallery->addItem($item);
        }

        return $this;
    }

    /**
     * Get the built components.
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * Create a button component.
     */
    protected function createButton(
        string $label,
        mixed $value = null,
        mixed $emoji = null,
        ?string $style = null,
        bool $disabled = false,
        bool $hidden = false,
        ?string $id = null,
        ?string $route = null,
        array $options = []
    ): ?Button {
        if ($hidden) {
            return null;
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
        }

        return $button;
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

    /**
     * Start a new action row.
     */
    public function row(): self
    {
        $this->ensureContainer();

        $this->currentActionRow = ActionRow::new();

        $this->rowButtons = [];

        $this->currentContainer->addComponent($this->currentActionRow);

        return $this;
    }

    /**
     * Add a select menu to the current action row.
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
                $select->addOption(Option::new(is_int($key) ? $value : $key, $value));

                continue;
            }

            $option = Option::new($value['label'] ?? $key, $value['value'] ?? $key)
                ->setDescription($value['description'] ?? null)
                ->setEmoji($value['emoji'] ?? null)
                ->setDefault($value['default'] ?? false);

            $select->addOption($option);
        }

        $this->currentContainer->addComponent($select);

        return $this;
    }
}
