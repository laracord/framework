<?php

namespace Laracord\Commands;

use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Command\Command as DiscordCommand;
use Discord\Parts\Interactions\Command\Option as DiscordOption;
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
     * Create a Discord command instance.
     */
    public function create(): DiscordCommand
    {
        $command = CommandBuilder::new()
            ->setName($this->getName())
            ->setDescription($this->getDescription());

        if ($this->getOptions()) {
            foreach ($this->getOptions() as $option) {
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
            $this->handle($interaction);

            return;
        }

        $this->user = $this->getUser($interaction->member->user);
        $this->server = $interaction->guild;

        if ($this->isAdminCommand() && ! $this->user->is_admin) {
            return;
        }

        $this->handle($interaction);
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
     *
     * @return string
     */
    public function getGuild()
    {
        return $this->guild;
    }

    /**
     * Retrieve the slash command options.
     *
     * @return array
     */
    public function getOptions()
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
