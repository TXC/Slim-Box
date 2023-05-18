<?php

declare(strict_types=1);

namespace TXC\Box\Environment;

use Composer\InstalledVersions;
use RuntimeException;

class Settings
{
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
                throw new RuntimeException(sprintf('Trying to fetch invalid setting "%s"', implode('.', $parents)));
            }
        }

        return $settings;
    }

    public static function load(): self
    {
        $settings = require __DIR__ . '/default-settings.php';
        if (file_exists(self::getAppRoot() . '/config/settings.php')) {
            $settings = array_merge($settings, require self::getAppRoot() . '/config/settings.php');
        }
        return new self($settings);
    }

    public static function getAppRoot(): string
    {
        //return InstalledVersions::getRootPackage()['install_path'];
        for ($i = 5; $i > 0; $i--) {
            if (!file_exists(dirname(__DIR__, $i) . '/vendor/autoload.php')) {
                continue;
            }
            return dirname(__DIR__, $i);
        }
        throw new RuntimeException('vendor/autoload.php could not be found. Did you run `php composer.phar install`?');
    }
}
