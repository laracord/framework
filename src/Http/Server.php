<?php

namespace Laracord\Http;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laracord\Laracord;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class Server
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The Laracord instance.
     */
    protected Laracord $bot;

    /**
     * The HTTP server instance.
     */
    protected ?HttpServer $server = null;

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
    public function __construct(Laracord $bot)
    {
        $this->bot = $bot;
        $this->app = $bot->getApplication();
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

        $this->getServer()->removeAllListeners();
        $this->getSocket()->close();

        $this->booted = false;

        $this->bot->console()->log('The HTTP server has been shutdown');
    }

    /**
     * Retrieve the HTTP server instance.
     *
     * @return \React\Http\HttpServer
     */
    public function getServer()
    {
        if ($this->server) {
            return $this->server;
        }

        return $this->server = new HttpServer($this->bot->getLoop(), function (ServerRequestInterface $request) {
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

            $this->app->instance('request', $request);

            /** @var \Laracord\Http\Kernel $kernel */
            $kernel = $this->app->make(Kernel::class);

            $kernel = $this->attachMiddleware($kernel);

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

        $this->bot->console()->error($e->getMessage());

        return new Response(
            500,
            ['Content-Type' => 'application/json'],
            json_encode(['code' => 500, 'message' => $response])
        );
    }

    /**
     * Attach the middleware to the kernel.
     */
    protected function attachMiddleware(Kernel $kernel): Kernel
    {
        if ($this->bot->prependMiddleware()) {
            foreach ($this->bot->prependMiddleware() as $middleware) {
                $kernel->prependMiddleware($middleware);
            }
        }

        if ($this->bot->middleware()) {
            foreach ($this->bot->middleware() as $middleware) {
                $kernel->pushMiddleware($middleware);
            }
        }

        return $kernel;
    }

    /**
     * Retrieve the socket server instance.
     *
     * @return \React\Socket\SocketServer
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * Retrieve the server address.
     */
    public function getAddress(): ?string
    {
        if ($this->address) {
            return $this->address;
        }

        $address = config('discord.http');

        if (! $address) {
            return null;
        }

        if (Str::startsWith($address, ':')) {
            $address = Str::start($address, '0.0.0.0');
        }

        $host = Str::before($address, ':');
        $port = Str::after($address, ':');

        if (! filter_var($host, FILTER_VALIDATE_IP)) {
            throw new Exception('Invalid HTTP server address');
        }

        if ($port > 65535 || $port < 1) {
            throw new Exception('Invalid HTTP server port');
        }

        return $this->address = $address;
    }

    /**
     * Determine if the server is booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }
}
