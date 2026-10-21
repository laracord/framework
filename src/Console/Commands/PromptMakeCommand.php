<?php

namespace Laracord\Console\Commands;

use Illuminate\Foundation\Console\ConsoleMakeCommand as FoundationConsoleMakeCommand;

class PromptMakeCommand extends FoundationConsoleMakeCommand
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'make:prompt';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Create a new console prompt';

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
        $relativePath = '/stubs/prompt.stub';

        return file_exists($customPath = $this->laravel->basePath(trim($relativePath, '/')))
            ? $customPath
            : __DIR__.$relativePath;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Console\Prompts';
    }
}
