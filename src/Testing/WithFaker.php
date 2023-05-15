<?php

declare(strict_types=1);

namespace TXC\Box\Testing;

use Faker\Factory;
use Faker\Generator;

trait WithFaker
{
    protected Generator $faker;

    protected function setUpFaker(string $locale = Factory::DEFAULT_LOCALE): void
    {
        $this->faker = Factory::create($locale);
    }
}
