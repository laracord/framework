<?php

namespace Laracord\Concerns;

use Exception;
use React\Promise\Promise;

trait CanAsync
{
    /**
     * Perform an asynchronous operation.
     */
    public static function handleAsync(callable $callback): Promise
    {
        return new Promise(function ($resolve, $reject) use ($callback) {
            if (! $loop = app('bot')?->getLoop()) {
                throw new Exception('The Laracord event loop is not available.');
            }

            $loop->futureTick(function () use ($callback, $resolve, $reject) {
                try {
                    $result = $callback();
                    $resolve($result);
                } catch (Exception $e) {
                    $reject($e);
                }
            });
        });
    }

    /**
     * Perform an asynchronous operation.
     */
    public function async(callable $callback): Promise
    {
        return static::handleAsync($callback);
    }
}
