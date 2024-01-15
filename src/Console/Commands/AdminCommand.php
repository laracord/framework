<?php

namespace Laracord\Console\Commands;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class AdminCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'bot:admin
                            {user : The user ID to promote to admin}
                            {--revoke : Revoke admin privileges}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Make the specified user an admin';

    /**
     * The user model.
     *
     * @var \Illuminate\Database\Eloquent\Model|null
     */
    protected $user;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! is_numeric($user = $this->argument('user'))) {
            $this->user = $this->getUserModel()::where('username', $user)->first();

            if (! $this->user) {
                $this->components->error("The user <fg=red>{$user}</> does not exist.");

                return;
            }
        }

        $this->user = $this->user ?? $this->getUserModel()::where('discord_id', $user)->first();

        if ($this->option('revoke')) {
            return $this->revoke();
        }

        if ($this->user && $this->user->is_admin) {
            $this->components->error("The user <fg=red>{$this->user->username}</> is already an admin.");

            return;
        }

        $this->handleAdmin();
    }

    /**
     * Handle the admin command.
     */
    protected function handleAdmin(): void
    {
        $user = $this->user ? [
            'id' => $this->user->discord_id,
            'username' => $this->user->username,
        ] : [];

        if (! $user) {
            $token = $this->getBotClass()::make($this)->getToken();

            $request = Http::withHeaders([
                'Authorization' => "Bot {$token}",
            ])->get("https://discord.com/api/users/{$user}");

            if ($request->failed()) {
                $this->components->error('Failed to fetch user data from Discord API.');

                return;
            }

            $user = $request->json();
        }

        if (! $this->components->confirm("Are you sure you want to make <fg=blue>{$user['username']}</> an admin?")) {
            return;
        }

        $user = $this->getUserModel()::updateOrCreate(['discord_id' => $user['id']], [
            'username' => $user['username'],
            'is_admin' => true,
        ]);

        if (! $user) {
            $this->components->error('Failed to update user.');

            return;
        }

        $this->components->info("User <fg=blue>{$user->username}</> is now an admin.");
    }

    /**
     * Revoke admin privileges from the specified user.
     */
    protected function revoke(): void
    {
        if (! $this->user) {
            $this->components->error("The user <fg=red>{$user}</> does not exist.");

            return;
        }

        if (! $this->user->is_admin) {
            $this->components->error("The user <fg=red>{$this->user->username}</> is not an admin.");

            return;
        }

        if (! $this->components->confirm("Are you sure you want to revoke admin privileges from <fg=blue>{$this->user->username}</>?")) {
            return;
        }

        $this->user->update(['is_admin' => false]);

        $this->components->info("User <fg=blue>{$this->user->username}</> is no longer an admin.");
    }

    /**
     * Get the bot class.
     */
    protected function getBotClass(): string
    {
        $class = Str::start($this->app->getNamespace(), '\\').'Bot';

        return class_exists($class) ? $class : 'Laracord';
    }

    /**
     * Get the user model class.
     */
    protected function getUserModel(): string
    {
        $model = $this->app->getNamespace().'Models\User';

        if (! class_exists($model)) {
            throw new Exception('The user model could not be found.');
        }

        return $model;
    }
}
