<?php

namespace Laracord\Http\Routing;

use Illuminate\Routing\UrlGenerator as BaseUrlGenerator;
use Illuminate\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class UrlGenerator extends BaseUrlGenerator implements UrlGeneratorContract
{
    public function __construct(Request $request = null)
    {
        if (!$request) {
            $appUrl = rtrim(config('app.url', 'http://localhost'), '/');
            $request = Request::create($appUrl);
        }

        parent::__construct(Route::getRoutes(), $request);
    }
}
