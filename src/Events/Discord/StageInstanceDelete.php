<?php

namespace Laracord\Events\Discord;

class StageInstanceDelete
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Channel\StageInstance $stageInstance,
    ) {
    }
}
