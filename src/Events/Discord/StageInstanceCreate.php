<?php

namespace Laracord\Events\Discord;

class StageInstanceCreate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Channel\StageInstance $stageInstance,
    ) {}
}
