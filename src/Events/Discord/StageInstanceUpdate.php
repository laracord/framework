<?php

namespace Laracord\Events\Discord;

class StageInstanceUpdate
{
    public function __construct(
        public readonly \Laracord\Laracord $laracord,
        public readonly \Discord\Parts\Channel\StageInstance $stageInstance,
        public readonly ?\Discord\Parts\Channel\StageInstance $oldStageInstance,
    ) {
    }
}
