<?php

namespace Kue;

/**
 * Interface for Queues
 *
 * @author Christoph Hochstrasser <christoph.hochstrasser@gmail.com>
 */
interface Queue
{
    /**
     * Blocks until a job is available, and returns it. This is used by the
     * worker script that's shipped with Spark.
     *
     * @return Job
     */
    function pop();

    /**
     * Pushes the job onto the queue.
     *
     * @param Job $job
     * @return void
     */
    function push(Job $job);

    /**
     * Flushes the queue.
     *
     * This is mainly useful for queues which are clients to a 3rd party system
     * which stores the jobs. This method is called after the response has been sent
     * to the client, and can be used to send all pushed jobs in one go.
     *
     * @return void
     */
    function flush();
}

