<?php

namespace Laracord\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Exception;

class DiscordCallbackController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            $discordUser = Socialite::driver('discord')
                ->setRequest($request)
                ->user();

            $model = app('bot')->getUserModel();

            if (! class_exists($model)) {
                return redirect()->to(app('bot')->getDiscordLoginFailedRedirect());
            }

            $user = $model::updateOrCreate(
                ['discord_id' => $discordUser->getId()],
                [
                    'username' => $discordUser->getName() ?? $discordUser->getNickname(),
                ]
            );

            $afterDiscordAuthCallback = app('bot')->getAfterDiscordAuthCallback();
            if (is_callable($afterDiscordAuthCallback)) {
                $afterDiscordAuthCallback($user, $discordUser);
            }

            auth()->login($user);

            return redirect()->to(app('bot')->getDiscordLoggedInRedirect());
        } catch (Exception) {
            return redirect()->to(app('bot')->getDiscordLoginFailedRedirect());
        }
    }
}
