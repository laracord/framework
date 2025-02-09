<?php

namespace Laracord\Bot\Concerns;

use Illuminate\Filesystem\Filesystem;
use ReflectionClass;

trait HasComponents
{
    /**
     * Discover classes in a directory.
     */
    protected function discover(string $baseClass, ?string $directory, ?string $namespace): array
    {
        if (blank($directory) || blank($namespace)) {
            return [];
        }

        $filesystem = app(Filesystem::class);

        if ((! $filesystem->exists($directory)) && (! str($directory)->contains('*'))) {
            return [];
        }

        $namespace = str($namespace);
        $discovered = [];

        foreach ($filesystem->allFiles($directory) as $file) {
            $variableNamespace = $namespace->contains('*') ? str_ireplace(
                ['\\'.$namespace->before('*'), $namespace->after('*')],
                ['', ''],
                str_replace([DIRECTORY_SEPARATOR], ['\\'], (string) str($file->getPath())->after(base_path())),
            ) : null;

            if (is_string($variableNamespace)) {
                $variableNamespace = (string) str($variableNamespace)->before('\\');
            }

            $class = (string) $namespace
                ->append('\\', $file->getRelativePathname())
                ->replace('*', $variableNamespace ?? '')
                ->replace([DIRECTORY_SEPARATOR, '.php'], ['\\', '']);

            if (! class_exists($class)) {
                continue;
            }

            if ((new ReflectionClass($class))->isAbstract()) {
                continue;
            }

            if (! is_subclass_of($class, $baseClass)) {
                continue;
            }

            $discovered[] = $class;
        }

        return $discovered;
    }
}
