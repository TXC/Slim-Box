<?php

namespace TXC\Box\Infrastructure\Resolvers;

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Symfony\Component\Finder\Finder;
use TXC\Box\Infrastructure\Application\Cache;
use TXC\Box\Infrastructure\Environment\Settings;

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
        $astLocator = (new BetterReflection())->astLocator();
        foreach ($finder as $file) {
            $reflector = new DefaultReflector(new SingleFileSourceLocator($file->getRealPath(), $astLocator));
            foreach ($reflector->reflectAllClasses() as $class) {
                if (!$class->getAttributesByName($attributeClassName)) {
                    // Class is not tagged with attribute.
                    continue;
                }
                $classes[] = $class->getName();
            }
        }

        return $classes;
    }
}
