<?php

namespace Laracord\Console\Commands;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
        $this->user = $this->resolveUser($this->argument('user'));

        if (! $this->user) {
            return;
        }

        if ($this->option('revoke')) {
            return $this->revoke();
        }

        $this->handleAdmin();
    }

    /**
     * Handle the admin command.
     */
    protected function handleAdmin(): void
    {
        if ($this->user->is_admin) {
            $this->components->error("The user <fg=red>{$this->user->username}</> is already an admin.");

            return;
        }

        if (! $this->components->confirm("Are you sure you want to make <fg=blue>{$this->user->username}</> a bot admin?")) {
            return;
        }

        $user = $this->user->update(['is_admin' => true]);

        if (! $user) {
            $this->components->error("Failed to make <fg=red>{$this->user->username}</> a bot admin.");

            return;
        }

        $this->components->info("User <fg=blue>{$this->user->username}</> is now a bot admin.");
    }

    /**
     * Revoke admin privileges from the specified user.
     */
    protected function revoke(): void
    {
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
            $token = $this->getBotClass()::make($this)->getToken();

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
}
