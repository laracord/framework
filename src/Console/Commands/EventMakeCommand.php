<?php

namespace Laracord\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laracord\Events\Event;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\search;
use function Laravel\Prompts\select;

class EventMakeCommand extends GeneratorCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'make:event';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Create a new Discord event';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Discord event';

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);

        $events = collect(Event::getEvents())->sortBy('name');

        $parameters = collect(
            File::json(__DIR__.'/../../../resources/data/events.json')
        )->flatMap(fn ($event) => $event);

        $event = $this->option('event');

        if (! $event) {
            $event = windows_os()
                ? select(
                    'Select a Discord event to listen for',
                    $events->pluck('name')->flip()->all(),
                    scroll: 15,
                )
                : search(
                    label: 'Select a Discord event to listen for',
                    placeholder: 'Search for an event...',
                    options: fn (string $value) => strlen($value) > 0
                        ? $events->pluck('name', 'key')->filter(fn ($name) => Str::contains(strtolower($name), strtolower($value)))->all()
                        : $events->pluck('name', 'key')->all(),
                    scroll: 15,
                );

            $event = $events->filter(fn ($e) => $e['name'] === $event)->keys()->first() ?? $event;
        }

        if (! $events->has($event)) {
            $this->components->error("The <fg=red>{$event}</> event does not exist.");

            return 1;
        }

        $event = $events->get($event);
        $eventClass = $event['class'];

        $attributes = $parameters->get($eventClass, []);

        $namespaces = collect($attributes)
            ->filter(fn ($namespace) => Str::contains($namespace, '\\'))
            ->unique()
            ->map(fn ($namespace) => "use {$namespace};")
            ->implode("\n");

        $attributes = collect($attributes)->map(function ($namespace, $attribute) {
            $class = Str::afterLast($namespace, '\\');

            if (Str::startsWith($namespace, '?')) {
                $class = Str::start($class, '?');
            }

            if (Str::startsWith($class, '$')) {
                return $class;
            }

            return "{$class} \${$attribute}";
        })->implode(', ');

        if ($namespaces) {
            $namespaces = "\n{$namespaces}";
        }

        return str_replace(
            ['{{ attributes }}', '{{ event }}', '{{ eventName }}', '{{ eventClass }}', '{{ namespaces }}'],
            [$attributes, $event['key'], $event['name'], $eventClass, $namespaces],
            $stub
        );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $relativePath = '/stubs/event.stub';

        return file_exists($customPath = $this->laravel->basePath(trim($relativePath, '/')))
            ? $customPath
            : __DIR__.$relativePath;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Events';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the event handler'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the Discord event already exists'],
            ['event', null, InputOption::VALUE_OPTIONAL, 'The Discord event listener that will be used for the event'],
        ];
    }
}
