<?php

namespace Laracord\Console\Commands;

use Laracord\Console\Concerns\WithLog;
use LaravelZero\Framework\Commands\Command as LaravelCommand;

abstract class Command extends LaravelCommand
{
    use WithLog;
}
