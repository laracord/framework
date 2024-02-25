<?php

namespace Laracord\Commands;

use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Command\Command as DiscordCommand;
use Discord\Parts\Interactions\Command\Option as DiscordOption;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Permissions\RolePermission;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laracord\Commands\Contracts\SlashCommand as SlashCommandContract;

abstract class SlashCommand extends AbstractCommand implements SlashCommandContract
{
    /**
     * The guild the command belongs to.
     *
     * @var string
     */
    protected $guild;

    /**
     * The permissions required to use the command.
     *
     * @var array
     */
    protected $permissions = [];

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
            ->setDescription($this->getDescription());

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

        return new DiscordCommand($this->discord(), $command);
    }

    /**
     * Handle the slash command.
     *
     * @param  \Discord\Parts\Interactions\Interaction  $interaction
     * @return void
     */
    abstract public function handle($interaction);

    /**
     * Maybe handle the slash command.
     *
     * @param  \Discord\Parts\Interactions\Interaction  $interaction
     * @return void
     */
    public function maybeHandle($interaction)
    {
        if (! $this->isAdminCommand()) {
            $this->parseOptions($interaction);

            $this->handle($interaction);

            $this->clearOptions();

            return;
        }

        $this->server = $interaction->guild;

        if ($this->isAdminCommand() && ! $this->isAdmin($interaction->member->user)) {
            return $interaction->respondWithMessage(
                $this
                    ->message('You do not have permission to run this command.')
                    ->title('Permission Denied')
                    ->error()
                    ->build(),
                ephemeral: true
            );
        }

        $this->parseOptions($interaction);

        $this->handle($interaction);

        $this->clearOptions();
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
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    protected function option($key = null, $default = null)
    {
        if (is_null($key) || ! $this->getOptions()) {
            return $this->getOptions();
        }

        return Arr::get($this->getOptions(), $key, $default);
    }

    /**
     * Set the command options.
     *
     * @return array
     */
    public function options()
    {
        return [];
    }

    /**
     * Retrieve the command signature.
     *
     * @return string
     */
    public function getSignature()
    {
        return Str::start($this->getName(), '/');
    }

    /**
     * Retrieve the slash command guild.
     */
    public function getGuild(): ?string
    {
        return $this->guild ?? null;
    }

    /**
     * Retrieve the slash command bitwise permission.
     */
    public function getPermissions(): ?string
    {
        if (! $this->permissions) {
            return null;
        }

        $permissions = collect($this->permissions)
            ->mapWithKeys(fn ($permission) => [$permission => true])
            ->all();

        return (new RolePermission($this->discord(), $permissions))->__toString();
    }

    /**
     * Retrieve the slash command options.
     *
     * @return array
     */
    public function getRegisteredOptions()
    {
        if ($this->registeredOptions) {
            return $this->registeredOptions;
        }

        $options = collect($this->options())->merge($this->options);

        if ($options->isEmpty()) {
            return $this->registeredOptions = null;
        }

        return $this->registeredOptions = $options->map(fn ($option) => $option instanceof DiscordOption
            ? $option
            : new DiscordOption($this->discord(), $option)
        )->map(fn ($option) => $option->setName(Str::slug($option->name)))->all();
    }
}
