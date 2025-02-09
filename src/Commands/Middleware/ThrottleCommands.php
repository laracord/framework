<?php

namespace Laracord\Commands\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Laracord\Discord\Facades\Message;

class ThrottleCommands implements Middleware
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(protected int $maxAttempts = 60, protected int $decayMinutes = 1)
    {
        //
    }

    /**
     * Handle the command.
     *
     * @return mixed
     */
    public function handle(Context $context, Closure $next)
    {
        $key = $this->resolveRequestSignature($context);

        if ($this->tooManyAttempts($key)) {
            Message::content('You are being rate limited. Please try again later.')
                ->error()
                ->reply($context->source);

            return;
        }

        $this->incrementAttempts($key);

        return $next($context);
    }

    /**
     * Resolve the unique request signature for the rate limiter.
     */
    protected function resolveRequestSignature(Context $context): string
    {
        return sha1($context->getUser()->id.'|'.$context->getGuildId().'|'.class_basename($context->command ?? $context->source));
    }

    /**
     * Determine if the user has too many attempts.
     */
    protected function tooManyAttempts(string $key): bool
    {
        return Cache::get($key, 0) >= $this->maxAttempts;
    }

    /**
     * Increment the attempts for the given key.
     */
    protected function incrementAttempts(string $key): void
    {
        $attempts = Cache::get($key, 0) + 1;

        Cache::put($key, $attempts, now()->addMinutes($this->decayMinutes));
    }
}
