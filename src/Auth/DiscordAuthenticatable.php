<?php

namespace Laracord\Auth;

trait DiscordAuthenticatable
{
    /**
     * Get the name of the unique identifier for the user.
     * Using 'discord_id' as the primary identifier since it's unique.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'discord_id';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->discord_id;
    }

    /**
     * Get the name of the password attribute for the user.
     * Since there are no passwords, we can return null or an empty string.
     *
     * @return string
     */
    public function getAuthPasswordName()
    {
        return null;
    }

    /**
     * Get the password for the user.
     * Since there are no passwords, return null to ensure no password-based auth.
     *
     * @return string|null
     */
    public function getAuthPassword()
    {
        return null;
    }

    /**
     * Get the token value for the "remember me" session.
     * Not using remember tokens for Discord-only auth.
     *
     * @return string|null
     */
    public function getRememberToken()
    {
        return null;
    }

    /**
     * Set the token value for the "remember me" session.
     * Not using remember tokens for Discord-only auth.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        //
    }

    /**
     * Get the column name for the "remember me" token.
     * Not using remember tokens for Discord-only auth.
     *
     * @return string|null
     */
    public function getRememberTokenName()
    {
        return null;
    }
}