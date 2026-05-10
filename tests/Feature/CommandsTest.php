<?php

use Laracord\Commands\Command;

class ValidCommand extends Command
{
    public function getName(): string
    {
        return 'valid-command';
    }
}

describe('in order to communicate with the bot, developer should be able to register commands into Laracord instance', function () {

    test('a command could be registered on laracord', function () {
        $laracord = app(\Laracord\Laracord::class);

        $laracord->registerCommand(ValidCommand::class);

        expect(ValidCommand::class)->toBeRegistered();
    });
});
