<?php

namespace Laracord\Console\Commands\Concerns;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laracord\Facades\Laracord;

trait ResolvesUser
{
    /**
     * Resolve the user.
     *
     * @return \Illuminate\Database\Eloquent\Model|void
     */
    protected function resolveUser(?string $user = null)
    {
        if (! $user) {
            $this->components->error('You must specify a valid user.');

            return;
        }

        if (! is_numeric($user)) {
            $model = $this->getUserModel()::where('username', $user)->first();

            if (! $model) {
                $this->components->error("The user <fg=red>{$user}</> does not exist.");

                return;
            }
        }

        $model = $model ?? $this->getUserModel()::where('discord_id', $user)->first();

        if (! $model) {
            $token = Laracord::getToken();

            $request = Http::withHeaders([
                'Authorization' => "Bot {$token}",
            ])->get("https://discord.com/api/users/{$user}");

            if ($request->failed()) {
                $this->components->error("Failed to fetch user <fg=red>{$user}</> from the Discord API.");

                return;
            }

            $user = $request->json();

            $model = $this->getUserModel()::updateOrCreate(['discord_id' => $user['id']], [
                'username' => $user['username'],
            ]);
        }

        return $model;
    }

    /**
     * Get the user model class.
     */
    protected function getUserModel(): string
    {
        $model = Str::start(app()->getNamespace(), '\\').'Models\\User';

        if (! class_exists($model)) {
            throw new Exception('The user model could not be found.');
        }

        return $model;
    }
}
