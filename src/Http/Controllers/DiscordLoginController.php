<?php

namespace Laracord\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class DiscordLoginController extends Controller
{
    public function __invoke(Request $request)
    {
        return Socialite::driver('discord')
            ->setRequest($request)
            ->scopes(app('bot')->getDiscordAuthScopes())
            ->redirect();
    }
}
