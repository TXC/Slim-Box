<?php

declare(strict_types=1);

namespace TXC\Box\Testing;

use Throwable;
use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use Psr\Container\ContainerInterface;

abstract class TestCase extends PHPUnit_TestCase
{
    private static ?ContainerInterface $container = null;

    protected array $beforeContainerDestroyedCallbacks = [];
    private ?Throwable $callbackException = null;

    protected function setUp(): void
    {
        $this->setUpTraits();
    }

    protected function setUpTraits(): array
    {
        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[WithContainer::class]) && method_exists($this, 'bootContainer')) {
            $this->bootContainer();
        }

        if (isset($uses[WithApplication::class]) && method_exists($this, 'bootApplication')) {
            $this->bootApplication();
        }

        if (isset($uses[DatabaseMigration::class]) && method_exists($this, 'runDatabaseMigrations')) {
            $this->runDatabaseMigrations();
        }

        if (isset($uses[DatabaseTruncation::class]) && method_exists($this, 'truncateDatabaseTables')) {
            $this->truncateDatabaseTables();
        }

        if (isset($uses[WithFaker::class]) && method_exists($this, 'setUpFaker')) {
            $this->setUpFaker();
        }

        foreach ($uses as $trait) {
            if (method_exists($this, $method = 'setUp' . class_basename($trait))) {
                $this->{$method}();
            }

            if (method_exists($this, $method = 'tearDown' . class_basename($trait))) {
                $this->beforeContainerDestroyedCallbacks(fn () => $this->{$method}());
            }
        }

        return $uses;
    }

    /**
     * @throws Throwable
     */
    protected function tearDown(): void
    {
        if (self::$container) {
            $this->callBeforeContainerDestroyedCallbacks();
        }
        $this->beforeContainerDestroyedCallbacks = [];

        if ($this->callbackException) {
            throw $this->callbackException;
        }
    }

    /**
     * Register a callback to be run before the container is destroyed.
     *
     * @param  callable  $callback
     * @return void
     */
    protected function beforeContainerDestroyedCallbacks(callable $callback): void
    {
        $this->beforeContainerDestroyedCallbacks[] = $callback;
    }

    /**
     * Execute the container's pre-destruction callbacks.
     *
     * @return void
     */
    protected function callBeforeContainerDestroyedCallbacks(): void
    {
        foreach ($this->beforeContainerDestroyedCallbacks as $callback) {
            try {
                $callback();
            } catch (Throwable $e) {
                if (! $this->callbackException) {
                    $this->callbackException = $e;
                }
            }
        }
    }
}
