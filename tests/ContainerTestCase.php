<?php

declare(strict_types=1);

namespace TXC\Box\Tests;

use TXC\Box\Testing\TestCase;
use TXC\Box\Testing\WithContainer;

abstract class ContainerTestCase extends TestCase
{
    use WithContainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootContainer();
    }
}
