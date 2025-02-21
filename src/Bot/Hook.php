<?php

namespace Laracord\Bot;

enum Hook: string
{
    /**
     * Called before the bot starts its boot process.
     */
    case BEFORE_BOOT = 'beforeBoot';

    /**
     * Called after all bot components are initialized.
     */
    case AFTER_BOOT = 'afterBoot';

    /**
     * Called before the bot begins its shutdown process.
     */
    case BEFORE_SHUTDOWN = 'beforeShutdown';

    /**
     * Called before the bot begins its restart process.
     */
    case BEFORE_RESTART = 'beforeRestart';

    /**
     * Called after the bot has completed its restart process.
     */
    case AFTER_RESTART = 'afterRestart';

    /**
     * Called after all commands (regular chat commands) are registered.
     */
    case AFTER_COMMANDS_REGISTERED = 'afterCommandsRegistered';

    /**
     * Called after all application commands (slash commands and context menus) are registered.
     */
    case AFTER_APPLICATION_COMMANDS_REGISTERED = 'afterApplicationCommandsRegistered';

    /**
     * Called after all event listeners are registered.
     */
    case AFTER_EVENTS_REGISTERED = 'afterEventsRegistered';

    /**
     * Called after all services are booted.
     */
    case AFTER_SERVICES_REGISTERED = 'afterServicesRegistered';

    /**
     * Called after the HTTP server has started successfully.
     */
    case AFTER_HTTP_SERVER_START = 'afterHttpServerStart';

    /**
     * Called before the HTTP server begins its shutdown process.
     */
    case BEFORE_HTTP_SERVER_STOP = 'beforeHttpServerStop';

    /**
     * Called after all interactions are registered.
     */
    case AFTER_INTERACTIONS_REGISTERED = 'afterInteractionsRegistered';
}
