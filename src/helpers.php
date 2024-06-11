<?php

if (! function_exists('laracord_path')) {
    /**
     * Retrieve the path to a file or directory relative to Laracord.
     */
    function laracord_path(string $path = '', bool $useBasePath = true): string
    {
        $basePath = $useBasePath ? '.laracord' : '';
        $appPath = \Phar::running(false) ? pathinfo(\Phar::running(false), PATHINFO_DIRNAME) : null;

        return match ($appPath) {
            null => base_path($basePath ? "{$basePath}/{$path}" : $path),
            default => $appPath.($basePath ? "/{$basePath}" : '').($path ? "/{$path}" : '')
        };
    }
}
