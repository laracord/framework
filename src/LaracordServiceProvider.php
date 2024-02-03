<?php

namespace Laracord;

use Illuminate\Http\Request;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\ViewServiceProvider;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class LaracordServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cache.php', 'cache');
        $this->mergeConfigFrom(__DIR__.'/../config/commands.php', 'commands');
        $this->mergeConfigFrom(__DIR__.'/../config/database.php', 'database');
        $this->mergeConfigFrom(__DIR__.'/../config/discord.php', 'discord');

        if ($this->isRoutingEnabled()) {
            $this->registerRouting();
        }
    }

    /**
     * Register routing support for the application.
     *
     * @return void
     */
    public function registerRouting()
    {
        $this->app->register(RoutingServiceProvider::class);
        $this->app->register(ViewServiceProvider::class);

        $this->app->singleton('psr17Factory', fn () => new Psr17Factory());
        $this->app->singleton('httpFoundationFactory', fn () => new HttpFoundationFactory());
        $this->app->singleton('psrHttpFactory', function () {
            $factory = $this->app->make('psr17Factory');

            return new PsrHttpFactory($factory, $factory, $factory, $factory);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->commands([
            Console\Commands\AdminCommand::class,
            Console\Commands\BootCommand::class,
            Console\Commands\ConsoleMakeCommand::class,
            Console\Commands\EventMakeCommand::class,
            Console\Commands\MakeCommand::class,
            Console\Commands\MakeSlashCommand::class,
            Console\Commands\ModelMakeCommand::class,
            Console\Commands\ServiceMakeCommand::class,
        ]);

        if ($this->isRoutingEnabled()) {
            $this->bootRouting();
        }
    }

    /**
     * Bootstrap the routing services for the application.
     *
     * @return void
     */
    public function bootRouting()
    {
        $psr17 = $this->app->make('psr17Factory');
        $foundation = $this->app->make('httpFoundationFactory');

        $request = Request::createFromBase(
            $foundation->createRequest(
                $psr17->createServerRequest('GET', '/')
            )
        );

        $this->app->instance('request', $request);
        $this->app->instance('url', new UrlGenerator($this->app['router']->getRoutes(), $this->app['request']));
    }

    /**
     * Determine if routing is enabled for the application.
     *
     * @return bool
     */
    public function isRoutingEnabled()
    {
        return (bool) $this->app['config']->get('discord.http');
    }
}
