<?php

declare(strict_types=1);

namespace WebChefs\QueueButler;

// Package
use WebChefs\QueueButler\Laravel\Worker;
use WebChefs\QueueButler\Exceptions\StopBatch;
use WebChefs\QueueButler\Laravel\WorkerOptions;

class BatchWorker extends Worker
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
     * @param  WorkerOptions  $options
     * @return void
     */
    public function batch($connectionName, $queue, WorkerOptions $options)
    {
        $this->options   = $options;
        $this->startTime = microtime(true);
        $this->jobCount  = 0;
        $this->daemon($connectionName, $queue, $options);
    }

    /**
     * Listen to the given queue in a loop.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  WorkerOptions  $options
     * @return void
     */
    public function daemon($connectionName, $queue, WorkerOptions $options)
    {
        try {
           parent::daemon($connectionName, $queue, $options);
        }
        catch (StopBatch $e) {
            // Check if the batch was cleanly stopped
            // Then do nothing
        }
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
    protected function stopIfNecessary(WorkerOptions $options, $lastRestart, $job = null)
    {
        parent::stopIfNecessary($options, $lastRestart, $job);
        $this->checkLimits();
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param  int   $seconds
     *
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
    protected function checkLimits(): void
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
    protected function isTimeLimit($timeLimit): bool
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
    protected function isJobLimit($jobLimit): bool
    {
        return $this->jobCount >= $jobLimit;
    }
}