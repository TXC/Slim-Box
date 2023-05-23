<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\DependencyInjection;

use DI\CompiledContainer;
use DI\Container;
use DI\Definition\Helper\AutowireDefinitionHelper;
use DI\Definition\Source\DefinitionSource;
use TXC\Box\Infrastructure\Environment\Settings;
use TXC\Box\Infrastructure\Resolvers\ClassAttributeResolver;
use TXC\Box\Infrastructure\Resolvers\InterfaceResolver;
use TXC\Box\Interfaces\CompilerPass;

class ContainerBuilder
{
    /** @var CompilerPass[] */
    private array $passes = [];
    private ?string $classAttributeCacheDir = null;
    private ?string $interfaceCacheDir = null;

    public function __construct(
        private readonly \DI\ContainerBuilder $containerBuilder,
        private readonly ClassAttributeResolver $classAttributeResolver,
        private readonly InterfaceResolver $interfaceResolver
    ) {
    }

    /**
     *  @param array<mixed>|string|DefinitionSource ...$definitions
     */
    public function addDefinitions(...$definitions): self
    {
        $this->containerBuilder->addDefinitions(...$definitions);

        return $this;
    }

    public function enableCompilation(
        string $directory,
        string $containerClass = 'CompiledContainer',
        string $containerParentClass = CompiledContainer::class
    ): self {
        $this->containerBuilder->enableCompilation(
            $directory,
            $containerClass,
            $containerParentClass,
        );

        return $this;
    }

    public function enableClassAttributeCache(string $directory): self
    {
        $this->classAttributeCacheDir = $directory;

        return $this;
    }

    public function enableInterfaceCache(string $directory): self
    {
        $this->interfaceCacheDir = $directory;

        return $this;
    }

    public function addCompilerPasses(CompilerPass ...$compilerPasses): self
    {
        foreach ($compilerPasses as $compilerPass) {
            $this->addCompilerPass($compilerPass);
        }

        return $this;
    }

    public function addCompilerPass(CompilerPass $pass): self
    {
        if (array_key_exists($pass::class, $this->passes)) {
            throw new \RuntimeException(sprintf(
                'CompilerPass %s already added. Cannot add the same pass twice',
                $pass::class
            ));
        }
        $this->passes[$pass::class] = $pass;

        return $this;
    }

    public function findDefinition(string $id): AutowireDefinitionHelper
    {
        return \DI\autowire($id);
    }

    /**
     * @return string[]
     */
    public function findTaggedWithClassAttribute(string $name, string ...$restrictToDirectories): array
    {
        return $this->classAttributeResolver->resolve($name, $restrictToDirectories, $this->classAttributeCacheDir);
    }

    /**
     * @return string[]
     */
    public function findClassesThatImplements(string $name, string ...$restrictToDirectories): array
    {
        return $this->interfaceResolver->resolve($name, $restrictToDirectories, $this->interfaceCacheDir);
    }

    public function build(): Container
    {
        $settings = Settings::load();
        foreach ($this->passes as $pass) {
            $pass->process($this, $settings);
        }
        if ($this->containerBuilder->isCompilationEnabled()) {
            // We need to add the auto wired classes to the container
            // to make sure they are compiled as well. This will boost performance,
            // see: https://php-di.org/doc/performances.html#optimizing-for-compilation
            $file = Settings::getAppRoot() . '/config/auto-wires.php';
            if (file_exists($file)) {
                $this->containerBuilder->addDefinitions(require $file);
            }
        }

        return $this->containerBuilder->build();
    }

    public static function create(): self
    {
        return new self(
            new \DI\ContainerBuilder(),
            new ClassAttributeResolver(),
            new InterfaceResolver()
        );
    }
}
