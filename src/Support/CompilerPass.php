<?php

declare(strict_types=1);

namespace TXC\Box\Support;

use Composer\InstalledVersions;
use TXC\Box\Console\ConsoleCommandCompilerPass;
use TXC\Box\Controller\RoutesCompilerPass;
use TXC\Box\Domain\DomainCompilerPass;
use TXC\Box\Middleware\MiddlewareCompilerPass;
use TXC\Box\Repository\RepositoryCompilerPass;

class CompilerPass
{
    public static function collect(): array
    {
        $packages = [];
        if (InstalledVersions::isInstalled('symfony/console')) {
            $packages = array_merge($packages, self::addSymfonyConsole());
        }
        if (InstalledVersions::isInstalled('doctrine/orm')) {
            $packages = array_merge($packages, self::addDoctrineOrm());
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

    protected static function addSkeleton(): array
    {
        return [
            // Compiler pass to auto discover Routes from controllers handlers.
            new RoutesCompilerPass(),
            // Compiler pass to auto discover Middleware.
            new MiddlewareCompilerPass(),
        ];
    }
}
