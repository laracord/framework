<?php

namespace Laracord\Http;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laracord\Bot\Hook;
use Laracord\Http\Handlers\StaticFileHandler;
use Laracord\Laracord;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer as Server;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;
use React\Socket\SocketServer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class HttpServer
{
    /**
     * The HTTP server instance.
     */
    protected ?Server $server = null;

    /**
     * The socket server instance.
     */
    protected SocketServer $socket;

    /**
     * The server address.
     */
    protected string $address = '';

    /**
     * Determine if the server is booted.
     */
    protected bool $booted = false;

    /**
     * The static file handler instance.
     */
    protected ?StaticFileHandler $staticFileHandler = null;

    /**
     * Create a new server instance.
     */
    public function __construct(protected Laracord $bot)
    {
        //
    }

    /**
     * Make a new server instance.
     */
    public static function make(Laracord $bot): self
    {
        return new static($bot);
    }

    /**
     * Boot the HTTP server.
     */
    public function boot(): self
    {
        if (! $this->getAddress() || ! Route::getRoutes()->getRoutes()) {
            return $this;
        }

        $this->socket = new SocketServer($this->getAddress(), [], $this->bot->getLoop());

        $this->getServer()->listen($this->socket);

        $this->booted = true;

        return $this;
    }

    /**
     * Shutdown the HTTP server.
     */
    public function shutdown(): void
    {
        if (! $this->isBooted()) {
            return;
        }

        $this->bot->callHook(Hook::BEFORE_HTTP_SERVER_STOP);

        $this->getServer()->removeAllListeners();
        $this->getSocket()->close();

        $this->booted = false;

        $this->bot->logger->info('The HTTP server has been shutdown');
    }

    /**
     * Retrieve the HTTP server instance.
     */
    public function getServer(): Server
    {
        if ($this->server) {
            return $this->server;
        }

        return $this->server = new Server($this->bot->getLoop(), function (ServerRequestInterface $request) {
            return $this->handleStaticFile($request)
                ->then(function ($response) use ($request) {
                    if ($response !== null) {
                        return $response;
                    }

                    return $this->handleLaravelRequest($request);
                })
                ->otherwise(fn (Throwable $e) => $this->handleError($e));
        });
    }

    /**
     * Handle a Laravel request through the kernel.
     */
    protected function handleLaravelRequest(ServerRequestInterface $request): Response
    {
        $headers = $request->getHeaders();

        $request = Request::create(
            $request->getUri()->getPath(),
            $request->getMethod(),
            $request->getQueryParams(),
            $request->getCookieParams(),
            [],
            $request->getServerParams(),
            $request->getBody()->getContents()
        );
        $request->headers->replace($headers);

        $this->bot->app->instance('request', $request);

        $this->configureMiddleware();

        /** @var \Laracord\Http\Kernel $kernel */
        $kernel = $this->bot->app->make(Kernel::class);

        try {
            $response = $kernel->handle($request);
            $kernel->terminate($request, $response);

            return new Response(
                $response->getStatusCode(),
                $response->headers->allPreserveCase(),
                $this->getResponseContent($response)
            );
        } catch (Throwable $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Configure the Laravel middleware for the request.
     */
    protected function configureMiddleware(): void
    {
        $this->bot->withMiddleware(function (Middleware $middleware) {
            $middleware
                ->remove([\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class])
                ->append([\Laracord\Http\Middleware\FlushState::class])
                ->api([
                    \Laracord\Http\Middleware\AuthorizeToken::class,
                ])
                ->alias([
                    'auth.token' => \Laracord\Http\Middleware\AuthorizeToken::class,
                ]);
        });
    }

    /**
     * Get the response content from a Laravel response.
     */
    protected function getResponseContent(SymfonyResponse $response): string
    {
        if ($response->getContent()) {
            return $response->getContent();
        }

        if ($response instanceof BinaryFileResponse) {
            return $response->getFile()->getContent();
        }

        return '';
    }

    /**
     * Handle a static file request.
     */
    protected function handleStaticFile(ServerRequestInterface $request): PromiseInterface
    {
        if (! $this->staticFileHandler) {
            $this->staticFileHandler = new StaticFileHandler;
        }

        return $this->staticFileHandler->handle($request)
            ->otherwise(fn () => null);
    }

    /**
     * Handle an error response.
     */
    protected function handleError(Throwable $e): Response
    {
        $message = 'Internal Server Error';

        if (! app()->isProduction()) {
            $message = Str::finish($message, ": {$e->getMessage()}");
        }

        report($e);

        return new Response(
            500,
            ['Content-Type' => 'application/json'],
            json_encode(['code' => 500, 'message' => $message])
        );
    }

    /**
     * Retrieve the socket server instance.
     */
    public function getSocket(): SocketServer
    {
        return $this->socket;
    }

    /**
     * Set the server address.
     */
    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Retrieve the server address.
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * Determine if the server is booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }
}
