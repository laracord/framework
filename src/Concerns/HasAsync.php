<?php

namespace Laracord\Concerns;

use Exception;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

trait HasAsync
{
    /**
     * Perform an asynchronous operation.
     */
    public static function handleAsync(callable $callback): Promise
    {
        return new Promise(function ($resolve, $reject) use ($callback) {
            if (! $loop = app(LoopInterface::class)) {
                throw new Exception('The event loop is not available.');
            }

            $loop->futureTick(function () use ($callback, $resolve, $reject) {
                try {
                    $resolve($callback());
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
