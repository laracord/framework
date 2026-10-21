<?php

namespace Laracord\Bot\Concerns;

use Discord\Parts\Interactions\Command\Option;
use Illuminate\Support\Arr;
use Laracord\Bot\Hook;
use Laracord\Commands\ApplicationCommand;
use Laracord\Commands\ContextMenu;

use function React\Async\await;
use function React\Promise\all;

trait HasApplicationCommands
{
    /**
     * The registered slash commands.
     */
    protected array $slashCommands = [];

    /**
     * The registered context menus.
     */
    protected array $contextMenus = [];

    /**
     * Register the bot application commands.
     */
    protected function bootApplicationCommands(): self
    {
        $normalize = function ($data) use (&$normalize) {
            if (is_object($data)) {
                $data = (array) $data;
            }

            if (is_array($data)) {
                ksort($data);

                return array_map($normalize, $data);
            }

            return $data;
        };

        $existing = [];

        $existing[] = $this->discord->application->commands->freshen();

        foreach ($this->discord->guilds as $guild) {
            $existing[] = $guild->commands->freshen();
        }

        $existing = all($existing)->then(fn ($commands) => collect($commands)
            ->flatMap(fn ($command) => $command->toArray())
            ->map(fn ($command) => collect($command->getCreatableAttributes())
                ->merge([
                    'id' => $command->id,
                    'guild_id' => $command->guild_id ?? null,
                    'dm_permission' => $command->guild_id ? null : ($command->dm_permission ?? false),
                    'default_permission' => $command->default_permission ?? true,
                ])
                ->all()
            )
            ->map(fn ($command) => array_merge($command, [
                'options' => json_decode(json_encode($command['options'] ?? []), true),
            ]))
            ->filter(fn ($command) => filled($command))
            ->keyBy('name')
        );

        $existing = await($existing);
        $existing = collect($existing);

        $registered = collect($this->slashCommands)
            ->merge($this->contextMenus)
            ->filter(fn ($command) => $command->isEnabled())
            ->mapWithKeys(function ($command) {
                $attributes = $command->create()->getCreatableAttributes();

                $attributes = collect($attributes)
                    ->merge([
                        'guild_id' => $command->getGuild() ?? null,
                        'dm_permission' => ! $command->getGuild() ? $command->canDirectMessage() : null,
                        'nsfw' => $command->isNsfw(),
                    ])
                    ->sortKeys()
                    ->all();

                return [$command->getName() => [
                    'state' => $command,
                    'attributes' => $attributes,
                ]];
            });

        $created = $registered->reject(fn ($command, $name) => $existing->has($name))->filter();
        $deleted = $existing->reject(fn ($command, $name) => $registered->has($name))->filter();

        $updated = $registered
            ->map(function ($command) {
                $attributes = collect($command['attributes'])
                    ->reject(fn ($value) => blank($value))
                    ->all();

                return array_merge($command, ['attributes' => $attributes]);
            })
            ->filter(function ($command, $name) use ($existing, $normalize) {
                if (! $existing->has($name)) {
                    return false;
                }

                $current = collect($existing->get($name))
                    ->forget('id')
                    ->reject(fn ($value) => blank($value));

                $attributes = collect($command['attributes'])
                    ->reject(fn ($value) => blank($value));

                $keys = collect($current->keys())
                    ->merge($attributes->keys())
                    ->unique();

                foreach ($keys as $key) {
                    $attribute = $current->get($key);
                    $value = $attributes->get($key);

                    $attribute = $normalize($attribute);
                    $value = $normalize($value);

                    if ($attribute === $value) {
                        continue;
                    }

                    return true;
                }

                return false;
            })
            ->each(function ($command) use ($existing) {
                $state = $existing->get($command['state']->getName());

                $current = Arr::get($command, 'attributes.guild_id');
                $existing = Arr::get($state, 'guild_id');

                if ($current && ! $existing) {
                    $this->unregisterApplicationCommand($state['id']);
                }

                if ((! $current && $existing) || $current !== $existing) {
                    $this->unregisterApplicationCommand($state['id'], $existing);
                }
            });

        if ($updated->isNotEmpty()) {
            $this->logger->warning("Updating <fg=yellow>{$updated->count()}</> application command(s).");

            $updated->each(function ($command) {
                $state = $command['state'];

                $this->registerApplicationCommand($state);
            });
        }

        if ($deleted->isNotEmpty()) {
            $this->logger->warning("Deleting <fg=yellow>{$deleted->count()}</> application command(s).");

            $deleted->each(fn ($command) => $this->unregisterApplicationCommand($command['id'], $command['guild_id'] ?? null));
        }

        if ($created->isNotEmpty()) {
            $this->logger->info("Creating <fg=blue>{$created->count()}</> new application command(s).");

            $created->each(fn ($command) => $this->registerApplicationCommand($command['state']));
        }

        if ($registered->isEmpty()) {
            return $this;
        }

        $registered->each(function ($command, $name) {
            $this->registerInteractions($name, $command['state']->interactions());

            if ($command['state'] instanceof ContextMenu) {
                $menu = $command['state'];

                $this->discord->listenCommand(
                    $name,
                    fn ($interaction) => rescue(fn () => $menu->maybeHandle($interaction))
                );

                $this->contextMenus[$menu::class] = $menu;

                return;
            }

            $command = $command['state'];

            $subcommands = collect($command->getRegisteredOptions())
                ->filter(fn (Option $option) => $option->type === Option::SUB_COMMAND)
                ->map(fn (Option $subcommand) => [$name, $subcommand->name]);

            $subcommandGroups = collect($command->getRegisteredOptions())
                ->filter(fn (Option $option) => $option->type === Option::SUB_COMMAND_GROUP)
                ->flatMap(fn (Option $group) => collect($group->options)
                    ->filter(fn (Option $subcommand) => $subcommand->type === Option::SUB_COMMAND)
                    ->map(fn (Option $subcommand) => [$name, $group->name, $subcommand->name])
                );

            $subcommands = $subcommands->merge($subcommandGroups);

            if ($subcommands->isNotEmpty()) {
                $subcommands->each(fn ($names) => $this->discord->listenCommand(
                    $names,
                    fn ($interaction) => rescue(fn () => $command->maybeHandle($interaction)),
                    fn ($interaction) => rescue(fn () => $command->maybeHandleAutocomplete($interaction))
                ));

                return;
            }

            $this->discord->listenCommand(
                $name,
                fn ($interaction) => rescue(fn () => $command->maybeHandle($interaction)),
                fn ($interaction) => rescue(fn () => $command->maybeHandleAutocomplete($interaction))
            );

            $this->slashCommands[$command::class] = $command;
        });

        $this->callHook(Hook::AFTER_APPLICATION_COMMANDS_REGISTERED);

        return $this;
    }

    /**
     * Register the specified application command.
     */
    protected function registerApplicationCommand(ApplicationCommand $command): void
    {
        if ($command->getGuild()) {
            $guild = $this->discord->guilds->get('id', $command->getGuild());

            if (! $guild) {
                $this->logger->warning("The <fg=yellow>{$command->getName()}</> command failed to register because the guild <fg=yellow>{$command->getGuild()}</> could not be found.");

                return;
            }

            $guild->commands->save($command->create());

            return;
        }

        $this->discord->application->commands->save($command->create());
    }

    /**
     * Unregister the specified application command.
     */
    protected function unregisterApplicationCommand(string $id, ?string $guildId = null): void
    {
        if ($guildId) {
            $guild = $this->discord->guilds->get('id', $guildId);

            if (! $guild) {
                $this->logger->warning("The command with ID <fg=yellow>{$id}</> failed to unregister because the guild <fg=yellow>{$guildId}</> could not be found.");

                return;
            }

            $guild->commands->delete($id);

            return;
        }

        $this->discord->application->commands->delete($id);
    }
}
