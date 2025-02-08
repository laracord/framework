<?php

namespace Laracord\Http;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laracord\Bot\Hook;
use Laracord\Laracord;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer as Server;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

            $this->bot->withMiddleware(function (Middleware $middleware) {
                $middleware
                    ->use([\Laracord\Http\Middleware\FlushState::class])
                    ->api([\Laracord\Http\Middleware\AuthorizeToken::class])
                    ->alias(['auth' => \Laracord\Http\Middleware\AuthorizeToken::class]);
            });

            /** @var \Laracord\Http\Kernel $kernel */
            $kernel = $this->bot->app->make(Kernel::class);

            try {
                $kernel->terminate($request, $response = $kernel->handle($request));
            } catch (Throwable $e) {
                return $this->handleError($e);
            }

            return new Response(
                $response->getStatusCode(),
                $response->headers->allPreserveCase(),
                $response->getContent() ?: ($response instanceof BinaryFileResponse ? $response->getFile()->getContent() : false) ?: ''
            );
        });
    }

    /**
     * Handle an error response.
     */
    protected function handleError(Throwable $e): Response
    {
        $response = 'Internal Server Error';

        if (! app()->isProduction()) {
            $response = Str::finish($response, ": {$e->getMessage()}");
        }

        report($e);

        return new Response(
            500,
            ['Content-Type' => 'application/json'],
            json_encode(['code' => 500, 'message' => $response])
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
