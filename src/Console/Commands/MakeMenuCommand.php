<?php

namespace Laracord\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeMenuCommand extends GeneratorCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'make:menu';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Create a new Discord context menu option';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Discord context menu';

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

        $command = $this->option('command') ?: Str::of($name)->classBasename()->kebab()->value();

        $title = Str::of($command)->replace('-', ' ')->apa();

        $stub = str_replace(['dummy:command', '{{ command }}', '{{ title }}'], [$command, $command, $title], $stub);

        return $stub;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $relativePath = '/stubs/context-menu.stub';

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
        return $rootNamespace.'\Menus';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the context menu'],
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
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the Discord context menu already exists'],
            ['command', null, InputOption::VALUE_OPTIONAL, 'The Discord context menu that will be used to invoke the class'],
        ];
    }
}
