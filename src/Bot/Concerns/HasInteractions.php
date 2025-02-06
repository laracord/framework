<?php

namespace Laracord\Bot\Concerns;

use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;
use Laracord\Commands\Middleware\Context;
use Laracord\Commands\Middleware\Middleware;

trait HasInteractions
{
    /**
     * The registered interaction routes.
     */
    protected array $interactions = [];

    /**
     * The interaction middleware.
     */
    protected array $interactionMiddleware = [];

    /**
     * Register the interaction routes.
     */
    protected function registerInteractions(string $name, array $routes = []): void
    {
        $routes = collect($routes)
            ->mapWithKeys(fn ($value, $route) => ["{$name}@{$route}" => $value])
            ->all();

        if (! $routes) {
            return;
        }

        $this->interactions = [
            ...$this->interactions,
            ...$routes,
        ];
    }

    /**
     * Get the registered interactions.
     */
    public function getInteractions(): array
    {
        return $this->interactions;
    }

    /**
     * Register an interaction middleware.
     */
    public function registerInteractionMiddleware(string|Middleware $middleware): self
    {
        if (is_string($middleware)) {
            if (! class_exists($middleware)) {
                throw new InvalidArgumentException("Middleware class [{$middleware}] does not exist.");
            }

            if (! is_subclass_of($middleware, Middleware::class)) {
                throw new InvalidArgumentException("Middleware class [{$middleware}] must implement the Middleware interface.");
            }
        }

        $this->interactionMiddleware[] = $middleware;

        return $this;
    }

    /**
     * Register multiple interaction middleware.
     */
    public function registerInteractionMiddlewares(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            $this->registerInteractionMiddleware($middleware);
        }

        return $this;
    }

    /**
     * Get the interaction middleware.
     */
    public function getInteractionMiddleware(): array
    {
        return $this->resolveCommandMiddleware($this->interactionMiddleware);
    }

    /**
     * Process the interaction through its middleware stack.
     */
    protected function processInteractionMiddleware(Interaction $interaction, callable $handler): mixed
    {
        $context = new Context(source: $interaction);

        return (new Pipeline($this->app))
            ->send($context)
            ->through($this->getInteractionMiddleware())
            ->then(fn (Context $context) => $handler($context->source));
    }

    /**
     * Handle the interaction routes.
     */
    protected function handleInteractions(): self
    {
        $this->discord->on(Event::INTERACTION_CREATE, function (Interaction $interaction) {
            $id = $interaction->data->custom_id;

            $handlers = collect($this->getInteractions())
                ->partition(fn ($route, $name) => ! Str::contains($name, '{'));

            $static = $handlers[0];
            $dynamic = $handlers[1];

            if ($route = $static->get($id)) {
                return rescue(fn () => $this->processInteractionMiddleware($interaction, fn ($interaction) => $route($interaction)));
            }

            if (! $route) {
                $route = $dynamic->first(fn ($route, $name) => Str::before($name, ':') === Str::before($id, ':'));
            }

            if (! $route) {
                return;
            }

            $parameters = [];
            $requiredParameters = [];

            if (Str::contains($id, ':')) {
                $parameters = explode(':', Str::after($id, ':'));
            }

            $routeName = $dynamic->keys()->first(fn ($name) => Str::before($name, ':') === Str::before($id, ':'));

            if ($routeName && preg_match_all('/\{(.*?)\}/', $routeName, $matches)) {
                $requiredParameters = $matches[1];
            }

            foreach ($requiredParameters as $index => $param) {
                if (! Str::endsWith($param, '?') && (! isset($parameters[$index]) || $parameters[$index] === '')) {
                    $this->bot->logger->error("Missing required parameter `{$param}` for interaction route `{$routeName}`.");

                    return;
                }
            }

            rescue(fn () => $this->processInteractionMiddleware(
                $interaction,
                fn ($interaction) => $route($interaction, ...$parameters)
            ));
        });

        return $this;
    }
}
