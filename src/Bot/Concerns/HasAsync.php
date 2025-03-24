<?php

namespace Laracord\Bot\Concerns;

use Exception;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

trait HasAsync
{
    /**
     * Perform an asynchronous operation.
     */
    public static function handleAsync(callable $callback): PromiseInterface
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
    public function async(callable $callback): PromiseInterface
    {
        return static::handleAsync($callback);
    }
}
