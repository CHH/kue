# Kue

_A minimalistic, generic and framework independent interface to job queues_

## Install

Install via [Composer][]:

    % wget http://getcomposer.org/composer.phar
    % php composer.phar require chh/kue:*@dev

[composer]: http://getcomposer.org

## Use

### Jobs

Jobs are classes which implement the `Kue\Job` interface. This interface
specifies one method — `run()`. The `run` method is invoked by the
worker script, which pulls jobs out of the queue.

- - -
> Why not just implement `callable`?

Because if the contract is that a job must be callable, then every
callback (even a Closure) can satisfy it. The problem with this approach
is, that workers usually run in an other process than the script that
puts the jobs into the queue. Closures and functions can't be
serialized, so there's no way to transport them to the worker process.
Objects can be serialized in one process and unserialized in another,
and retain all their state and context.
- - -

### Queues

A queue implements the `Kue\Queue` interface. This interface looks as
follows:

```php
interface Kue\Queue
{
    function push(Kue\Job $job);
    function pop();
    function flush();
}
```

The queue's responsibility is, to be the transport layer between the user
who pushes jobs into the queue, and the worker script which pulls them
out by polling `pop()` for jobs to process.

The `flush()` method should be called by the client after jobs have been
pushed, and can be used to send multiple jobs with a more efficient
transport mechanism — like a batch request.

Kue ships with a `Kue\LocalQueue` for development environments. This
queue sends jobs to a local network socket on `push()`, and receives on
this network socket when `pop()` is called.

For production it's recommended to use something like SQS behind the
Queue interface.

### Workers

Workers take the queue, and start polling with the `pop()` method for
jobs.

```php
interface Kue\Worker extends \Evenement\EventEmitterInterface
{
    function process(Kue\Queue $queue);
}
```

Workers should abstract the strategy for processing the queued jobs. The
worker implementations which are shipped with Kue should be fully
sufficient for most use cases.

Kue ships with these workers out of the box:

* `Kue\SequentialWorker` — a simple worker which calls the queue's
  `pop()` method in a `for` loop, and processes jobs strictly one after
  the other. This is simpler and works on any platform, but should be
  only used for development or on Windows.
* `Kue\PreforkingWorker` — an advanced worker, which starts a master
  process and forks a pool of worker processes, which all call `pop()` individually.
  It can run jobs concurrently and is more fault tolerant, because when
  a worker dies, the master simply starts up a new one. You will want to
  use this worker in production. It only works on __*nix__ platforms,
  like OSX and Linux.

All workers are [Evenement][] Event Emitters and emit following standard events:

* `init`: Emitted before the job gets run, gets called with the job as
  argument.
* `success`: A job was processed successfully. The handler receives the
  job as argument.
* `exception`: An exception occured while running the job. The job and
  exception are passed as arguments.

The `Kue\PreforkingWorker` supports following additional events:

* `beforeFork`: This is emitted before the worker process gets forked from
  the parent.
* `afterFork`: Emitted from the child process after it was forked from
  the master. Use this to reinitialize resources in the worker process.

[Evenement]: https://github.com/igorw/evenement

These workers are typically managed by the Symfony Console Command,
which ships with Kue:

```php
use Symfony\Component\Console\Application;
use Kue\Command\Worker;
use Kue\LocalQueue;

$worker = new Worker(new LocalQueue);

$app = new Application;
$app->add($worker);

$app->run();
```

The queue worker can the be run with the `kue:worker` command inside the
Symfony Console Application. The worker implementations can be switched
with the `-c` flag:

* `1`: The `Kue\SequentialWorker` is used.
* `>1`: The `Kue\PreforkingWorker` is used, with a pool of `c` workers.

