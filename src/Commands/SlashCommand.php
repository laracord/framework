<?php

namespace Laracord\Commands;

use Discord\Builders\CommandBuilder;
use Discord\Helpers\Collection;
use Discord\Parts\Interactions\Command\Choice;
use Discord\Parts\Interactions\Command\Command as DiscordCommand;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laracord\Commands\Contracts\SlashCommand as SlashCommandContract;
use Laracord\Commands\Middleware\Context;

abstract class SlashCommand extends ApplicationCommand implements SlashCommandContract
{
    /**
     * The command options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * The registered command options.
     *
     * @var array
     */
    protected $registeredOptions = [];

    /**
     * The parsed command options.
     *
     * @var array
     */
    protected $parsedOptions = [];

    /**
     * Create a Discord command instance.
     */
    public function create(): DiscordCommand
    {
        $command = CommandBuilder::new()
            ->setName($this->getName())
            ->setDescription($this->getDescription())
            ->setType($this->getType())
            ->setDmPermission($this->canDirectMessage())
            ->setNsfw($this->isNsfw());

        if ($permissions = $this->getPermissions()) {
            $command = $command->setDefaultMemberPermissions($permissions);
        }

        if ($this->getRegisteredOptions()) {
            foreach ($this->getRegisteredOptions() as $option) {
                $command = $command->addOption($option);
            }
        }

        $command = collect($command->toArray())
            ->put('guild_id', $this->getGuild())
            ->filter()
            ->all();

        return new DiscordCommand($this->discord, $command);
    }

    /**
     * Process the command through its middleware stack.
     */
    protected function processMiddleware(Interaction $interaction): mixed
    {
        $context = new Context(
            source: $interaction,
            options: $this->getOptions(),
            command: $this,
        );

        return (new Pipeline($this->bot()->app))
            ->send($context)
            ->through($this->getMiddleware())
            ->then(fn (Context $context) => $this->resolveHandler([
                'interaction' => $context->source,
            ]));
    }

    /**
     * Maybe handle the slash command.
     */
    public function maybeHandle(Interaction $interaction): void
    {
        if (! $this->isAdminCommand()) {
            $this->parseOptions($interaction);

            $this->processMiddleware($interaction);

            $this->clearOptions();

            return;
        }

        if ($this->isAdminCommand() && ! $this->isAdmin($interaction->member->user)) {
            $this->handleDenied($interaction);

            return;
        }

        $this->parseOptions($interaction);

        $this->processMiddleware($interaction);

        $this->clearOptions();
    }

    /**
     * Maybe handle the slash command's autocomplete.
     */
    public function maybeHandleAutocomplete(Interaction $interaction): array
    {
        if (! $this->autocomplete()) {
            return [];
        }

        return $this->handleAutocomplete($interaction->data->options, $interaction);
    }

    /**
     * Handle the slash command's autocomplete.
     */
    protected function handleAutocomplete(Collection $options, Interaction $interaction, string $parent = ''): array
    {
        foreach ($options as $option) {
            $path = $parent
                ? rtrim("{$parent}.{$option->name}", '.')
                : $option->name;

            $value = Arr::get($this->autocomplete(), $path);

            if ($option->focused && $value) {
                $choices = is_callable($value)
                    ? $value($interaction, $option->value)
                    : Arr::wrap($value);

                return collect($choices)->map(function ($choice, $key) use ($choices) {
                    if ($choice instanceof Choice) {
                        return $choice;
                    }

                    if (Arr::isList($choices)) {
                        $key = $choice;
                    }

                    return Choice::new($this->discord(), $key, $choice);
                })->values()->take(25)->all();
            }

            if ($option->type === Option::SUB_COMMAND || $option->type === Option::SUB_COMMAND_GROUP) {
                return $this->handleAutocomplete($option->options, $interaction, $path);
            }
        }

        return [];
    }

    /**
     * Parse the options inside of the interaction.
     *
     * We serialize the options and then decode them back to an array
     * to get rid of excess data.
     */
    protected function parseOptions(Interaction $interaction): void
    {
        $this->parsedOptions = json_decode($interaction->data->options->serialize(), true);
    }

    /**
     * Get the parsed options.
     */
    protected function getOptions(): array
    {
        return $this->parsedOptions ?? [];
    }

    /**
     * Clear the parsed options.
     */
    protected function clearOptions(): void
    {
        $this->parsedOptions = [];
    }

    /**
     * Retrieve the parsed command options.
     */
    protected function option(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key) || ! $this->getOptions()) {
            return $this->getOptions();
        }

        return Arr::get($this->getOptions(), $key, $default);
    }

    /**
     * Retrieve the option value.
     */
    protected function value(?string $option = null, mixed $default = null): mixed
    {
        $options = $this->flattenOptions($this->getOptions());

        if (is_null($option)) {
            return $options;
        }

        return $options[$option] ?? $default;
    }

    /**
     * Set the command options.
     */
    public function options(): array
    {
        return [];
    }

    /**
     * Set the autocomplete choices.
     */
    public function autocomplete(): array
    {
        return [];
    }

    /**
     * Retrieve the command signature.
     */
    public function getSignature(): string
    {
        return Str::start($this->getName(), '/');
    }

    /**
     * Retrieve the slash command options.
     */
    public function getRegisteredOptions(): ?array
    {
        if ($this->registeredOptions) {
            return $this->registeredOptions;
        }

        $options = collect($this->options())->merge($this->options);

        if ($options->isEmpty()) {
            return $this->registeredOptions = null;
        }

        return $this->registeredOptions = $options->map(fn ($option) => $option instanceof Option
            ? $option
            : new Option($this->discord(), $option)
        )->map(fn ($option) => $option->setName(Str::slug($option->name)))->all();
    }

    /**
     * Flatten the options into dot notated keys.
     */
    protected function flattenOptions(array $options, ?string $parent = null): array
    {
        return collect($options)->flatMap(function ($option) use ($parent) {
            $key = $parent ? "{$parent}.{$option['name']}" : $option['name'];

            if (is_array($option) && isset($option['options'])) {
                $options = $this->flattenOptions($option['options'], $key);

                if (array_key_exists('value', $option)) {
                    return [
                        ...[$key => $option['value']],
                        ...$options,
                    ];
                }

                return $options;
            }

            return isset($option['value'])
                ? [$key => $option['value']]
                : [];
        })->all();
    }
}
