<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\Resolvers;

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use TXC\Box\Infrastructure\Application\Cache;
use TXC\Box\Infrastructure\Environment\Settings;

abstract class AbstractResolver
{
    private static ?string $cacheDir = null;
    protected array $directories;
    protected array $classNames;
    protected array $classData = [];

    public static function setCacheDir(string $directory): void
    {
        self::$cacheDir = $directory;
    }

    public static function resolve(string $className, string ...$directories): array
    {
        $instance = new static();
        return $instance->for($className)
                        ->in(...$directories)
                        ->checkCache()[$className];
    }

    public static function locate(string $className, string ...$directories): array
    {
        $instance = new static();
        return $instance->for($className)
                        ->in(...$directories)
                        ->findClasses()[$className];
    }

    public static function search(): static
    {
        return new static();
    }

    public function in(string ...$restrictToDirectories): self
    {
        $this->directories = $this->validateDirectories($restrictToDirectories);
        return $this;
    }

    public function for(string ...$lookingFor): self
    {
        $this->classNames = $lookingFor;
        return $this;
    }

    public function compile(): self
    {
        if (!self::$cacheDir) {
            throw new \UnexpectedValueException('Invalid value for cacheDir');
        }

        foreach ($this->classNames as $className) {
            $cache = new Cache($className, self::$cacheDir);
            if (!$cache->exists()) {
                $cache->compile($this->findClasses()[$className]);
            }
        }

        return $this;
    }

    public function checkCache(): array
    {
        if (self::$cacheDir) {
            $cachedData = [];
            foreach ($this->classNames as $className) {
                $cache = new Cache($className, self::$cacheDir);
                if (!$cache->exists()) {
                    $cachedData[$className] = require $cache->compile($this->findClasses()[$className]);
                } else {
                    $cachedData[$className] = require $cache->get();
                }
            }
            return $cachedData;
        }

        return $this->findClasses();
    }

    public function findClasses(): array
    {
        if (count($this->classNames) == 1 && isset($this->classData[current($this->classNames)])) {
            return $this->classData[current($this->classNames)];
        }

        $finder = new Finder();
        $finder->files()->in($this->directories)->name('*.php');

        foreach ($finder as $file) {
            try {
                foreach ($this->getReflector($file)->reflectAllClasses() as $class) {
                    foreach ($this->classNames as $className) {
                        if (!isset($this->classData[$className])) {
                            $this->classData[$className] = [];
                        }
                        if (!$this->matches($className, $class)) {
                            continue;
                        }
                        $this->classData[$className][] = $class->getName();
                    }
                }
            } catch (\Roave\BetterReflection\Reflector\Exception\IdentifierNotFound) {
                continue;
            }
        }
        return $this->classData;
    }

    protected function validateDirectories(array $restrictToDirectories = []): array
    {
        $appRoot = Settings::getAppRoot();
        $possibleDirectories = array_map(
            fn (string $dir) => $appRoot . '/' . $dir,
            $restrictToDirectories ?: ['src']
        );
        $searchInDirectories = array_filter(
            $possibleDirectories,
            fn (string $dir) => is_dir($dir)
        );
        if (empty($searchInDirectories)) {
            throw new \RuntimeException('Directory paths invalid');
        }
        return $searchInDirectories;
    }

    protected function getReflector(?SplFileInfo $file = null): DefaultReflector
    {
        $reflection = new BetterReflection();
        $astLocator = $reflection->astLocator();
        $sourceStubber = $reflection->sourceStubber();

        $aggregatedSourceLocators = [];
        if (!empty($file)) {
            $aggregatedSourceLocators[] = new SourceLocator\Type\SingleFileSourceLocator(
                $file->getRealPath(),
                $astLocator
            );
        }
        if (file_exists(Settings::getAppRoot() . '/vendor/autoload.php')) {
            $aggregatedSourceLocators[] = new SourceLocator\Type\ComposerSourceLocator(
                require Settings::getAppRoot() . '/vendor/autoload.php',
                $astLocator
            );
        }

        $aggregatedSourceLocators[] = new SourceLocator\Type\PhpInternalSourceLocator(
            $astLocator,
            $sourceStubber
        );
        $aggregatedSourceLocators[] = new SourceLocator\Type\EvaledCodeSourceLocator(
            $astLocator,
            $sourceStubber
        );
        $aggregatedSourceLocators[] = new SourceLocator\Type\AutoloadSourceLocator(
            $astLocator,
            $reflection->phpParser()
        );

        $sourceLocator = new SourceLocator\Type\MemoizingSourceLocator(
            new SourceLocator\Type\AggregateSourceLocator($aggregatedSourceLocators)
        );
        return new DefaultReflector($sourceLocator);
    }

    abstract protected function matches(string $lookingFor, ReflectionClass $class): bool;
}
