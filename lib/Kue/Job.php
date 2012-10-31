<?php

namespace Kue;

/**
 * Interface for jobs
 *
 * @author Christoph Hochstrasser <christoph.hochstrasser@gmail.com>
 */
interface Job
{
    /**
     * Invoked by the worker script
     *
     * @return void
     */
    function run();
}

