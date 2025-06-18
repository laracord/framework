<?php

namespace Laracord\Bot\Concerns;

use Closure;

trait HasDiscordAuth
{
    /**
     * The scopes that are requested during authentication with Discord.
     */
    protected array $discordAuthScopes = ['identify'];

    
    /**
     * The URL that the user should be redirected to after successful authentication.
     */
    protected string $discordLoggedInRedirect = '/';

    /**
     * The URL that the user should be redirected to after unsuccessful authentication.
     */
    protected string $discordLogInFailedRedirect = '/';

    /**
     * The closure that should be executed after a successful Discord login.
     */
    protected ?Closure $afterDiscordAuthCallback = null;

    /**
     * Set the discord Oauth2 scopes.
     */
    public function withDiscordAuthScopes(array $discordAuthScopes): self
    {
        $this->discordAuthScopes = $discordAuthScopes;

        return $this;
    }

    /**
     * Get the discord Oauth2 scopes.
     */
    public function getDiscordAuthScopes(): array
    {
        return $this->discordAuthScopes;
    }

    /**
     * Set the redirect URL after successful Discord authentication.
     */
    public function withDiscordLoggedInRedirect(string $url): self
    {
        $this->discordLoggedInRedirect = $url;

        return $this;
    }

    /**
     * Get the redirect URL after successful Discord authentication.
     */
    public function getDiscordLoggedInRedirect(): string
    {
        return $this->discordLoggedInRedirect;
    }

    /**
     * Set the redirect URL after failed Discord authentication.
     */
    public function withDiscordLoginFailedRedirect(string $url): self
    {
        $this->discordLogInFailedRedirect = $url;

        return $this;
    }

    /**
     * Get the redirect URL after failed Discord authentication.
     */
    public function getDiscordLoginFailedRedirect(): string
    {
        return $this->discordLogInFailedRedirect;
    }

    /**
     * Set the closure that should be executed after a successful Discord login.
     */
    public function afterDiscordAuthCallback(?Closure $afterDiscordAuthCallback): self
    {
        $this->afterDiscordAuthCallback = $afterDiscordAuthCallback;

        return $this;
    }

    /**
     * Get the closure that should be executed after a successful Discord login.
     */
    public function getAfterDiscordAuthCallback(): ?Closure
    {
        return $this->afterDiscordAuthCallback;
    }
}
