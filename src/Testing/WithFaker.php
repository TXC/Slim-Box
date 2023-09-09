<?php

declare(strict_types=1);

namespace TXC\Box\Testing;

use Faker\Factory;
use Faker\Generator;

trait WithFaker
{
    protected static Generator $faker;

    public static function setUpFaker(string $locale = Factory::DEFAULT_LOCALE): void
    {
        self::$faker = Factory::create($locale);
    }

    public static function getFaker(): Generator
    {
        if (empty(self::$faker)) {
            self::setUpFaker();
        }
        return self::$faker;
    }
}
