<?php

namespace Laracord;

enum Hook: string
{
    /**
     * Hook fired before the bot boots.
     */
    case BEFORE_BOOT = 'beforeBoot';

    /**
     * Hook fired after the bot boots.
     */
    case AFTER_BOOT = 'afterBoot';
}
