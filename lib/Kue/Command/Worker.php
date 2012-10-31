<?php

namespace Kue\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Kue\Queue;
use Kue\Job;
use Kue\SequentialWorker;
use Kue\PreforkingWorker;

class Worker extends Command
{
    protected $log;
    protected $queue;

    function __construct(Queue $queue)
    {
        $this->queue = $queue;

        $this->log = new Logger('kue:worker');
        $this->log->pushHandler(new StreamHandler(STDERR));

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('kue:worker')
            ->setDescription('Processes jobs put into the queue')
            ->addOption('workers', 'c', InputOption::VALUE_REQUIRED, 'Number of workers', 1)
            ->addOption('require', 'r', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'File(s) to require before accepting jobs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $require = $input->getOption('require');

        foreach ($require as $file) {
            require($file);
        }

        if ($input->getOption('workers') > 1) {
            $worker = new PreforkingWorker($input->getOption('workers'));
        } else {
            $worker = new SequentialWorker;
        }

        $output->writeln(sprintf('Processing jobs using %s on queue %s', get_class($worker), get_class($this->queue)));
        $output->writeln('Stop with [CTRL]+[c]');

        $log = $this->log;

        $worker->on('init', function(Job $job) use ($log) {
            $log->addInfo(sprintf('Accepted job %s', get_class($job)), array('job' => $job));
        });

        $worker->on('error', function(Job $job, $code, $message, $file, $line) use ($log) {
            $log->addError(
                sprintf('Job %s failed: "%s" in file %s:%d', get_class($job), $message, $file, $line),
                array('job' => $job)
            );
        });

        $worker->on('exception', function(Job $job, \Exception $exception) use ($log) {
            $log->addError(sprintf('Job "%s" failed: %s', get_class($job), $exception), array('job' => $job));
        });

        $worker->on('success', function(Job $job) use ($log) {
            $log->addInfo(sprintf('Job "%s" finished successfully', get_class($job)), array('job' => $job));
        });

        $worker->process($this->queue);
    }
}

