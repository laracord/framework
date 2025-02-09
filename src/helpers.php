<?php

if (! function_exists('laracord')) {
    /**
     * Retrieve the bot instance.
     */
    function laracord(): Laracord
    {
        return app('bot');
    }
}

if (! function_exists('laracord_path')) {
    /**
     * Retrieve the path to a file or directory relative to Laracord.
     */
    function laracord_path(string $path = '', bool $basePath = true): string
    {
        $binary = \Phar::running(false);

        $basePath = $basePath ? '.laracord' : '';
        $appPath = $binary ? pathinfo($binary, PATHINFO_DIRNAME) : null;

        return match ($appPath) {
            null => base_path($basePath ? "{$basePath}/{$path}" : $path),
            default => $appPath.($basePath ? "/{$basePath}" : '').($path ? "/{$path}" : '')
        };
    }
}
