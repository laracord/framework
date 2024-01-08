<?php

namespace Laracord\Console\Commands;

use Illuminate\Foundation\Console\ConsoleMakeCommand as FoundationConsoleMakeCommand;

class ConsoleMakeCommand extends FoundationConsoleMakeCommand
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'make:console-command';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Create a new console command';

    /**
     * {@inheritdoc}
     */
    protected function getNameInput(): string
    {
        return ucfirst(parent::getNameInput());
    }

    /**
     * {@inheritdoc}
     */
    protected function getStub(): string
    {
        $relativePath = '/stubs/console.stub';

        return file_exists($customPath = $this->laravel->basePath(trim($relativePath, '/')))
            ? $customPath
            : __DIR__.$relativePath;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Console\Commands';
    }
}
