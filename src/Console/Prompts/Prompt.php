<?php

namespace Laracord\Console\Prompts;

use Laracord\Console\Commands\Command;

abstract class Prompt extends Command
{
    /**
     * Make a new prompt instance.
     */
    public static function make(): self
    {
        return new static;
    }
}
