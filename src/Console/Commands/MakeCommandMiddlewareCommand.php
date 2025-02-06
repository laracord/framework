<?php

namespace Laracord\Console\Commands;

use Illuminate\Foundation\Console\ConsoleMakeCommand as FoundationConsoleMakeCommand;

class MakeCommandMiddlewareCommand extends FoundationConsoleMakeCommand
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'make:command-middleware';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Create a new command middleware';

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
        $relativePath = '/stubs/command-middleware.stub';

        return file_exists($customPath = $this->laravel->basePath(trim($relativePath, '/')))
            ? $customPath
            : __DIR__.$relativePath;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Commands\Middleware';
    }
}
