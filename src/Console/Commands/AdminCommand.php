<?php

namespace Laracord\Console\Commands;

use Laracord\Console\Concerns\ResolvesUser;

class AdminCommand extends Command
{
    use ResolvesUser;

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
            return $this->revokeAdmin();
        }

        $this->handleAdmin();
    }

    /**
     * Handle the admin promotion.
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
     * Revoke the user's admin privileges.
     */
    protected function revokeAdmin(): void
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
}
