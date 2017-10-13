<?php

namespace WebChefs\QueueButler;

// Package
use WebChefs\QueueButler\BatchOptions;
use WebChefs\QueueButler\Exceptions\StopBatch;

// Framework
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;

class BatchRunner extends Worker
{

    /**
     * @var int
     */
    protected $jobCount;

    /**
     * @var float
     */
    protected $startTime;

    /**
     * Listen to the given queue in a loop.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  BatchOptions  $options
     * @return void
     */
    public function batch($connectionName, $queue, BatchOptions $options)
    {
        $this->options   = $options;
        $this->startTime = microtime(true);
        $this->jobCount  = 0;

        try {
           $this->daemon($connectionName, $queue, $options);
        }
        catch (StopBatch $excption) {
            // Check if the batch was cleanly stopped
            // The batch hit a limit
            return;
        }
    }

    /**
     * Process the given job.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  string  $connectionName
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     */
    protected function runJob($job, $connectionName, WorkerOptions $options)
    {
        $this->validOptions($options);

        parent::runJob($job, $connectionName, $options);
        $this->jobCount++;
    }

    /**
     * Determine if the batch should process on this iteration.
     *
     * @return bool
     */
    protected function daemonShouldRun(WorkerOptions $options)
    {
        $this->checkLimits();
        return parent::daemonShouldRun($options);
    }

    /**
     * Raise the after queue job event.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseAfterJobEvent($connectionName, $job)
    {
        $this->jobCount++;
        parent::raiseAfterJobEvent($connectionName, $job);
        $this->checkLimits();
    }

    /**
     * Stop the process if necessary.
     *
     * @param  WorkerOptions  $options
     * @param  int  $lastRestart
     */
    protected function stopIfNecessary(WorkerOptions $options, $lastRestart)
    {
        parent::stopIfNecessary($options, $lastRestart);
        $this->checkLimits();
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param  int   $seconds
     * @return void
     */
    public function sleep($seconds)
    {
        $this->checkLimits();
        parent::sleep($seconds);
    }

    /**
     * Stop listening and bail out of the script.
     *
     * @return void
     */
    public function stop($status = 0)
    {
        // Cleanly handle stopping a batch without resorting to killing the process
        throw new StopBatch();
    }

    /**
     * Check our batch limits and stop the command if we reach a limit.
     *
     * @param  WorkerOptions $options
     */
    protected function checkLimits()
    {
        if ($this->isTimeLimit($this->options->timeLimit) || $this->isJobLimit($this->options->jobLimit)) {
            $this->stop();
        }
    }

    /**
     * Check if the batch timelimit has been reached.
     *
     * @param  init     $timeLimit
     *
     * @return boolean
     */
    protected function isTimeLimit($timeLimit)
    {
        return (microtime(true) - $this->startTime) > $timeLimit;
    }

    /**
     * Check if the batch job limit has been reached.
     *
     * @param  int        $jobLimit
     *
     * @return boolean
     */
    protected function isJobLimit($jobLimit)
    {
        return $this->jobCount >= $jobLimit;
    }

    /**
     * Use type hinting for validation were we cant change method argument
     * types of parent.
     *
     * @param  BatchOptions $options
     */
    protected function validOptions(BatchOptions $options)
    {
        // Should be empty.
    }

}