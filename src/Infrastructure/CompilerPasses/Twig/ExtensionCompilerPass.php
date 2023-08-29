<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\CompilerPasses\Twig;

use Twig\Extension\ExtensionInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use TXC\Box\Infrastructure\DependencyInjection\ContainerBuilder;
use TXC\Box\Infrastructure\Environment\Settings;
use TXC\Box\Infrastructure\Resolvers\InterfaceResolver;
use TXC\Box\Interfaces\CompilerPass;

use function DI\autowire;

class ExtensionCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $container, Settings $settings): void
    {
        $searchDirectories = [
            'src/Twig/Extensions',
            'vendor/txc/slim-box/src/Twig/Extensions',
        ];

        $extensionDefinition = $container->findDefinition(ExtensionContainer::class);
        $runtimeDefinition = $container->findDefinition(RuntimeLoaderContainer::class);

        $extensionDefinition->method(
            'registerExtension',
            autowire(\Symfony\Bridge\Twig\Extension\TranslationExtension::class)
                ->constructorParameter(
                    'translator',
                    \DI\get(\Symfony\Contracts\Translation\TranslatorInterface::class)
                )
        );
        $results = InterfaceResolver::search()->in(...$searchDirectories)
                                              ->for(ExtensionInterface::class, RuntimeLoaderInterface::class)
                                              ->findClasses();
        foreach ($results as $interface => $classes) {
            if ($interface === ExtensionInterface::class) {
                foreach ($classes as $class) {
                    $extensionDefinition->method('registerExtension', autowire($class));
                }
            } elseif ($interface === RuntimeLoaderInterface::class) {
                foreach ($classes as $class) {
                    $runtimeDefinition->method('registerRuntimeLoader', autowire($class));
                }
            }
        }

        $container->addDefinitions([
            ExtensionContainer::class => $extensionDefinition,
            RuntimeLoaderContainer::class => $runtimeDefinition,
        ]);
    }
}
