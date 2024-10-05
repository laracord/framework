<?php

namespace Laracord\Events\Discord;

class GuildAuditLogEntryCreate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Guild\AuditLog\Entry $entry,
    ) {
    }
}
