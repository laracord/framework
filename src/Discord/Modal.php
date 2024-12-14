<?php

namespace Laracord\Discord;

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\TextInput;
use Discord\Parts\Interactions\Interaction;
use Exception;
use Illuminate\Support\Str;
use React\Promise\PromiseInterface;

class Modal
{
    /**
     * The interaction instance.
     */
    protected ?Interaction $interaction = null;

    /**
     * The modal title.
     */
    protected string $title = '';

    /**
     * The modal ID.
     */
    protected ?string $id = null;

    /**
     * The modal components.
     */
    protected array $components = [];

    /**
     * The modal submit callback.
     *
     * @var callable|null
     */
    protected $submit = null;

    /**
     * Create a new Discord modal instance
     */
    public function __construct(?string $title = null, ?Interaction $interaction = null)
    {
        $this->title = $title;
        $this->interaction = $interaction;
    }

    /**
     * Make a new Discord modal instance
     */
    public static function make(?string $title = null, ?Interaction $interaction = null): self
    {
        return new static($title, $interaction);
    }

    /**
     * Set the modal title.
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the modal ID.
     */
    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set the modal components.
     */
    public function components(array $components): self
    {
        $this->components = [...$this->components, ...$components];

        return $this;
    }

    /**
     * Set the modal submit callback.
     */
    public function submit(callable $submit): self
    {
        $this->submit = $submit;

        return $this;
    }

    /**
     * Add a text input component to the modal.
     */
    public function text(
        string $label,
        ?string $key = null,
        ?int $minLength = null,
        ?int $maxLength = null,
        ?string $placeholder = null,
        ?string $value = null,
        bool $required = false
    ): self {
        $this->components[] = TextInput::new($label, TextInput::STYLE_SHORT)
            ->setCustomId($key ?? Str::camel($label))
            ->setMinLength($minLength)
            ->setMaxLength($maxLength)
            ->setPlaceholder($placeholder)
            ->setValue($value)
            ->setRequired($required);

        return $this;
    }

    /**
     * Add a paragraph input component to the modal.
     */
    public function paragraph(
        string $label,
        ?string $key = null,
        ?int $minLength = null,
        ?int $maxLength = null,
        ?string $placeholder = null,
        ?string $value = null,
        bool $required = false
    ): self {
        $this->components[] = TextInput::new($label, TextInput::STYLE_PARAGRAPH)
            ->setCustomId($key ?? Str::camel($label))
            ->setMinLength($minLength)
            ->setMaxLength($maxLength)
            ->setPlaceholder($placeholder)
            ->setValue($value)
            ->setRequired($required);

        return $this;
    }

    /**
     * Show the modal.
     */
    public function show(?Interaction $interaction = null): PromiseInterface
    {
        $interaction = $interaction ?? $this->interaction;

        return $interaction->showModal(
            $this->getTitle(),
            $this->getId() ?? Str::camel($this->getTitle()),
            $this->getComponents(),
            $this->getSubmit()
        );
    }

    /**
     * Retrieve the modal components.
     */
    public function getComponents(): array
    {
        $components = collect($this->components)
            ->map(fn ($component) => ActionRow::new()->addComponent($component));

        if ($components->isEmpty()) {
            throw new Exception('The modal must have at least one component.');
        }

        return $components->all();
    }

    /**
     * Retrieve the modal title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Retrieve the modal ID.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Retrieve the modal submit callback.
     */
    public function getSubmit(): ?callable
    {
        return $this->submit;
    }
}
