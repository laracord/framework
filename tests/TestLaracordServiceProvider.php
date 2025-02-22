<?php

namespace Tests;

use Laracord\Laracord;
use Laracord\LaracordServiceProvider;

class TestLaracordServiceProvider extends LaracordServiceProvider
{
    public function bot(Laracord $bot): Laracord
    {
        return $bot;
    }
}
