<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\Environment;

use RuntimeException;

class Settings
{
    private static ?string $appRoot = null;

    public function __construct(
        /** @var array<mixed> $settings */
        private readonly array $settings
    ) {
    }

    public function get(string $parents): mixed
    {
        $settings = $this->settings;
        $parents = explode('.', $parents);

        foreach ($parents as $parent) {
            if (is_array($settings) && (isset($settings[$parent]) || array_key_exists($parent, $settings))) {
                $settings = $settings[$parent];
            } else {
                var_dump($this->settings);
                throw new RuntimeException(sprintf('Trying to fetch invalid setting "%s"', implode('.', $parents)));
            }
        }

        return $settings;
    }

    public static function load(): self
    {
        $settings = require __DIR__ . '/../../Config/settings.php';
        if (file_exists(self::getAppRoot() . '/config/settings.php')) {
            $appSettings = require self::getAppRoot() . '/config/settings.php';
            $settings = self::arrayMergeRecursiveDistinct($settings, $appSettings);
        }
        return new self($settings);
    }

    private static function arrayMergeRecursiveDistinct(array &$array1, array &$array2): array
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

    public static function getAppRoot(): string
    {
        if (self::$appRoot) {
            return self::$appRoot;
        }
        if (file_exists(getcwd() . '/vendor/autoload.php')) {
            return self::$appRoot = getcwd();
        } elseif (file_exists(getcwd() . '/../vendor/autoload.php')) {
            return self::$appRoot = realpath(getcwd() . '/../');
        }
        //return InstalledVersions::getRootPackage()['install_path'];
        for ($i = 6; $i > 0; $i--) {
            if (!file_exists(dirname(__DIR__, $i) . '/vendor/autoload.php')) {
                continue;
            }
            return self::$appRoot = dirname(__DIR__, $i);
        }
        throw new RuntimeException('vendor/autoload.php could not be found. Did you run `php composer.phar install`?');
    }
}
