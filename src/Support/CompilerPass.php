<?php

declare(strict_types=1);

namespace TXC\Box\Support;

use Composer\InstalledVersions;
use TXC\Box\Infrastructure\CompilerPasses\Console\ConsoleCommandCompilerPass;
use TXC\Box\Infrastructure\CompilerPasses\Domain\DomainCompilerPass;
use TXC\Box\Infrastructure\CompilerPasses\EventListeners\EventListenerCompilerPass;
use TXC\Box\Infrastructure\CompilerPasses\Middleware\MiddlewareCompilerPass;
use TXC\Box\Infrastructure\CompilerPasses\Routes\RoutesCompilerPass;
use TXC\Box\Infrastructure\CompilerPasses\Twig\ExtensionCompilerPass;

class CompilerPass
{
    public static function collect(): array
    {
        $packages = [];
        if (PHP_SAPI === 'cli' && InstalledVersions::isInstalled('symfony/console')) {
            $packages = array_merge($packages, self::addSymfonyConsole());
        }
        if (InstalledVersions::isInstalled('doctrine/orm')) {
            $packages = array_merge($packages, self::addDoctrineOrm());
        }
        if (PHP_SAPI !== 'cli' && InstalledVersions::isInstalled('slim/twig-view')) {
            $packages = array_merge($packages, self::addTwigExtensions());
        }

        return array_merge($packages, self::addSkeleton());
    }

    protected static function addSymfonyConsole(): array
    {
        return [
            // Compiler pass to auto discover console commands.
            new ConsoleCommandCompilerPass(),
        ];
    }

    protected static function addDoctrineOrm(): array
    {
        return [
            // Compiler pass to auto discover Domain Entity handlers.
            new DomainCompilerPass(),
            // Compiler pass to auto discover Domain Registries handlers.
            //new RepositoryCompilerPass(),
        ];
    }

    protected static function addTwigExtensions(): array
    {
        return [
            // Compiler pass to find Twig extensions
            new ExtensionCompilerPass(),
        ];
    }

    protected static function addSkeleton(): array
    {
        $passes = [
            // Compiler pass to auto discover EventListeners.
            new EventListenerCompilerPass(),
        ];

        if (PHP_SAPI !== 'cli') {
            // Compiler pass to auto discover Routes from controllers handlers.
            $passes[] = new RoutesCompilerPass();
            // Compiler pass to auto discover Middleware.
            $passes[] = new MiddlewareCompilerPass();
        }

        return $passes;
    }
}
