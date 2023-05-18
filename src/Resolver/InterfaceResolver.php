<?php

declare(strict_types=1);

namespace TXC\Box\Resolver;

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
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
        $astLocator = (new BetterReflection())->astLocator();
        foreach ($finder as $file) {
            $reflector = new DefaultReflector(new SingleFileSourceLocator($file->getRealPath(), $astLocator));
            foreach ($reflector->reflectAllClasses() as $class) {
                if (!$class->implementsInterface($interfaceName)) {
                    // Class is not tagged with attribute.
                    continue;
                }
                $classes[] = $class->getName();
            }
        }

        return $classes;
    }
}
