<?php

namespace Laracord\Console\Commands;

use Illuminate\Support\Collection;
use Laracord\Console\Concerns\ResolvesUser;

class TokenMakeCommand extends Command
{
    use ResolvesUser;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'bot:token
                            {user : The user to generate a token for}
                            {--regenerate : Regenerate the user\'s token}
                            {--revoke : Revoke the user\'s token}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Generate an API token for the specified user.';

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

        if ($this->option('regenerate')) {
            return $this->regenerateToken();
        }

        if ($this->option('revoke')) {
            return $this->revokeToken();
        }

        $this->handleToken();
    }

    /**
     * Handle the token generation.
     */
    protected function handleToken(bool $force = false): void
    {
        $tokens = $this->getTokens();

        if ($tokens->isNotEmpty() && ! $force) {
            $this->components->error("The user <fg=red>{$this->user->username}</> already has a token.");

            return;
        }

        $token = $this->user->createToken('token', ['http'])->plainTextToken;

        $this->components->info("The token for <fg=blue>{$this->user->username}</> has been generated.");
        $this->components->bulletList([$token]);
    }

    /**
     * Revoke the user's token.
     */
    protected function revokeToken(): void
    {
        $tokens = $this->getTokens();

        if ($tokens->isEmpty()) {
            $this->components->error("The user <fg=red>{$this->user->username}</> does not have a token.");

            return;
        }

        $tokens->each->delete();

        $this->components->info("The token for <fg=blue>{$this->user->username}</> has been revoked.");
    }

    /**
     * Regenerate the user's token.
     */
    protected function regenerateToken(): void
    {
        $tokens = $this->getTokens();

        if (! $tokens->isEmpty()) {
            $tokens->each->delete();
        }

        $this->handleToken(true);
    }

    /**
     * Retrieve the user's token(s).
     */
    protected function getTokens(): Collection
    {
        return $this->user->tokens->filter(fn ($token) => $token->name === 'token');
    }
}
