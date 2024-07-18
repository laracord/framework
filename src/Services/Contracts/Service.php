<?php

namespace Laracord\Services\Contracts;

interface Service
{
    /**
     * Handle the service.
     *
     * @return mixed
     */
    public function handle();
}
