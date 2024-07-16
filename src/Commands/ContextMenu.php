<?php

namespace Laracord\Commands;

use Discord\Builders\CommandBuilder;
use Discord\Helpers\Collection;
use Discord\Parts\Interactions\Command\Choice;
use Discord\Parts\Interactions\Command\Command as DiscordCommand;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Permissions\RolePermission;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laracord\Commands\Contracts\ContextMenu as ContextMenuContract;

abstract class ContextMenu extends AbstractCommand implements ContextMenuContract
{
    /**
     * The permissions required to use the command.
     *
     * @var array
     */
    protected $permissions = [];

    /**
     * Create a Discord command instance.
     */
    public function create(): DiscordCommand
    {
        $command = CommandBuilder::new()
            ->setName($this->getCleanName())
            ->setType($this->getType())
            ->setDescription($this->getDescription());

        if ($permissions = $this->getPermissions()) {
            $command = $command->setDefaultMemberPermissions($permissions);
        }

        $command = $command->toArray();

        unset($command['description']);
        $command['name'] = $this->getName();

        $command = collect($command)
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

        $this->handle($interaction);
    }

    /**
     * Retrieve the option value.
     */
    protected function value(?string $option = null, mixed $default = null): mixed
    {
//        $options = $this->flattenOptions($this->getOptions());
//
//        if (is_null($option)) {
//            return $options;
//        }
//
//        return $options[$option] ?? $default;
        return $default;
    }

//    /**
//     * Set the command options.
//     */
//    public function options(): array
//    {
//        return [];
//    }
//
//    /**
//     * Set the autocomplete choices.
//     */
//    public function autocomplete(): array
//    {
//        return [];
//    }

    /**
     * Retrieve the command signature.
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->getName();
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

//    /**
//     * Retrieve the slash command options.
//     */
//    public function getRegisteredOptions(): ?array
//    {
//        if ($this->registeredOptions) {
//            return $this->registeredOptions;
//        }
//
//        $options = collect($this->options())->merge($this->options);
//
//        if ($options->isEmpty()) {
//            return $this->registeredOptions = null;
//        }
//
//        return $this->registeredOptions = $options->map(fn ($option) => $option instanceof Option
//            ? $option
//            : new Option($this->discord(), $option)
//        )->map(fn ($option) => $option->setName(Str::slug($option->name)))->all();
//    }
//
//    /**
//     * Flatten the options into dot notated keys.
//     */
//    protected function flattenOptions(array $options, ?string $parent = null): array
//    {
//        return collect($options)->flatMap(function ($option) use ($parent) {
//            $key = $parent ? "{$parent}.{$option['name']}" : $option['name'];
//
//            if (is_array($option) && isset($option['options'])) {
//                $options = $this->flattenOptions($option['options'], $key);
//
//                if (array_key_exists('value', $option)) {
//                    return [
//                        ...[$key => $option['value']],
//                        ...$options,
//                    ];
//                }
//
//                return $options;
//            }
//
//            return isset($option['value'])
//                ? [$key => $option['value']]
//                : [];
//        })->all();
//    }

    public function getCleanName() {
        // Current Discord-PHP doesn't support context menu names with spaces, we'll work around this for the moment.
        return str_replace(' ', '-', strtolower($this->name));
    }
}
