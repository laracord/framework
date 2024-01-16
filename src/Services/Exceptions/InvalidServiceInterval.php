<?php

namespace Laracord\Services\Exceptions;

use Exception;

class InvalidServiceInterval extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $name
     * @return void
     */
    public function __construct($name = '')
    {
        parent::__construct("The {$name} service interval must be greater than 0.");
    }
}
