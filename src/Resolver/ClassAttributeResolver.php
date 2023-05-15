<?php

namespace TXC\Box\Resolver;

use TXC\Box\Application\Cache;
use TXC\Box\Environment\Settings;
use Symfony\Component\Finder\Finder;

class ClassAttributeResolver
{
    /**
     * @param string[] $restrictToDirectories
     *
     * @return string[]
     */
    public function resolve(
        string $attributeClassName,
        array $restrictToDirectories = [],
        ?string $classAttributeCacheDir = null
    ): array {
        $appRoot = Settings::getAppRoot();

        if ($classAttributeCacheDir) {
            $cache = new Cache($attributeClassName, $classAttributeCacheDir);
            if (!$cache->exists()) {
                return require $cache->compile(
                    $this->searchForClasses($attributeClassName, $restrictToDirectories)
                );
            }

            return require $cache->get();
        }

        return $this->searchForClasses($attributeClassName, $restrictToDirectories);
    }

    /**
     * @param string[] $restrictToDirectories
     *
     * @return string[]
     */
    private function searchForClasses(
        string $attributeClassName,
        array $restrictToDirectories = [],
    ): array {
        $appRoot = Settings::getAppRoot();
        $searchInDirectories = array_map(
            fn (string $dir) => $appRoot . '/' . $dir,
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
                if (!(new \ReflectionClass($class))->getAttributes($attributeClassName)) {
                    // Class is not tagged with attribute.
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
