<?php

namespace Laracord\Http;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laracord\Laracord;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

class Server
{
    /**
     * The Laracord instance.
     *
     * @var \Laracord\Laracord
     */
    protected $bot;

    /**
     * The HTTP server instance.
     *
     * @var \React\Http\HttpServer
     */
    protected $server;

    /**
     * The socket server instance.
     *
     * @var \React\Socket\SocketServer
     */
    protected $socket;

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
     *
     * @return void
     */
    public function __construct(Laracord $bot)
    {
        $this->bot = $bot;
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
            $request = Request::create($request->getUri()->getPath(), $request->getMethod(), $request->getQueryParams(), [], [], $_SERVER, $request->getBody()->getContents());

            foreach ($headers as $header => $values) {
                $request->headers->set($header, $values);
            }

            app()->instance('request', $request);

            $kernel = class_exists($kernel = Str::start(app()->getNamespace(), '\\').'Http\\Kernel')
                ? app()->make($kernel)
                : app()->make(Kernel::class);

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

            try {
                $response = $kernel->handle($request);
            } catch (Throwable $e) {
                return $this->handleError($e);
            }

            if ($response->getStatusCode() !== 200 && app()->isProduction()) {
                return new Response(
                    $response->getStatusCode(),
                    ['Content-Type' => 'application/json'],
                    json_encode(['status' => $response->getStatusCode()])
                );
            }

            return new Response(
                $response->getStatusCode(),
                $response->headers->allPreserveCaseWithoutCookies(),
                $response->getContent() ?: $response->getFile()?->getContent()
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
