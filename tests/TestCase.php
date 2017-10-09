<?php

namespace WebChefs\QueueButler\Tests;

// PHP
use DomainException;

// Package
use WebChefs\QueueButler\Tests\Concerns\TestsQueueDb;

// Framework
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;

abstract class TestCase extends LaravelTestCase
{

    /**
     * @var string
     */
    protected $testEnvPath;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $this->testEnvPath = __DIR__;
        $this->setUpTraitEnv();

        $this->app = require $this->discoverApp(__DIR__);

        $this->app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * A recursive method that works works backwards through the directory
     * structure until it finds "bootstrap/app.php".
     *
     * This should normally resolve to __DIR__ . '../../boostrap/app.php'
     *
     * @param  string $path
     *
     * @return string
     */
    protected function discoverApp($path)
    {
        $file = $path . DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, ['bootstrap', 'app.php']);

        if (file_exists($file)) {
            return $file;
        }

        // Go up a level
        $path = dirname($path, 1);

        // Check if we have reached the end
        if ($path == '.' || $path == DIRECTORY_SEPARATOR) {
            throw new DomainException('Lravel "bootstramp/app.php" could not be discovered.');
        }

        // Try again (recursive)
        return $this->discoverApp($path);
    }

    /**
     * Let testing helper traits run pre-application booting environmental
     * changes.
     *
     * @return void
     */
    protected function setUpTraitEnv()
    {
        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[TestsQueueDb::class])) {
            $this->runTestQueueDbEnv();
        }
    }

    /**
     * Boot the testing helper traits.
     *
     * @return void
     */
    protected function setUpTraits()
    {
        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[TestsQueueDb::class])) {
            $this->runTestQueueDb();
        }

        return parent::setUpTraits();
    }


}