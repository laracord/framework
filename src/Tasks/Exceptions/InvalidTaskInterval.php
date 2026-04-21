<?php

namespace Laracord\Tasks\Exceptions;

use Exception;

class InvalidTaskInterval extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $name
     * @return void
     */
    public function __construct($name = '')
    {
        parent::__construct("The {$name} task interval must be greater than 0.");
    }
}
