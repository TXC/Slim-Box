<?php

declare(strict_types=1);

namespace TXC\Box\Resolver;

use TXC\Box\Application\Cache;
use TXC\Box\Environment\Settings;
use Symfony\Component\Finder\Finder;

class InterfaceResolver
{
    /**
     * @param string[] $restrictToDirectories
     *
     * @return string[]
     */
    public function resolve(
        string $interfaceName,
        array $restrictToDirectories = [],
        ?string $interfaceCacheDir = null
    ): array {
        $appRoot = Settings::getAppRoot();

        if ($interfaceCacheDir) {
            $cache = new Cache($interfaceName, $interfaceCacheDir);
            if (!$cache->exists()) {
                return require $cache->compile(
                    $this->searchForClasses($interfaceName, $restrictToDirectories)
                );
            }

            return require $cache->get();
        }

        return $this->searchForClasses($interfaceName, $restrictToDirectories);
    }

    /**
     * @param string[] $restrictToDirectories
     *
     * @return string[]
     */
    private function searchForClasses(
        string $interfaceName,
        array $restrictToDirectories = [],
    ): array {
        $appRoot = Settings::getAppRoot();
        $searchInDirectories = array_map(
            fn(string $dir) => $appRoot . '/' . $dir,
            $restrictToDirectories ?: ['src']
        );

        $finder = new Finder();
        $finder->files()->in($searchInDirectories)->name('*.php');

        $classes = [];
        foreach ($finder as $file) {
            $class = trim(str_replace(
                $appRoot . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR,
                '',
                $file->getRealPath()
            ));
            $class = 'App\\' . str_replace(
                [DIRECTORY_SEPARATOR, '.php'],
                ['\\', ''],
                $class
            );

            try {
                if (!(new \ReflectionClass($class))->implementsInterface($interfaceName)) {
                    // Class does not implement interface.
                    continue;
                }
            } catch (\ReflectionException) {
                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }
}
