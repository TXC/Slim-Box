<?php

declare(strict_types=1);

namespace TXC\Box\Support;

use TXC\Box\Console\ConsoleCommandCompilerPass;
use TXC\Box\Controller\RoutesCompilerPass;
use TXC\Box\Middleware\MiddlewareCompilerPass;
use TXC\Box\Repository\RepositoryCompilerPass;

class CompilerPass
{
    public static function collect(): array
    {
        $packages = [];
        /*
        if (InstalledVersions::isInstalled('monolog/monolog')) {
            $packages = array_merge($packages, self::addMonolog());
        }
        */

        return array_merge($packages, self::addSkeleton());
    }

    protected static function addSkeleton(): array
    {
        return [
            // Compiler pass to auto discover Routes from controllers handlers.
            new RoutesCompilerPass(),
            // Compiler pass to auto discover console commands.
            new ConsoleCommandCompilerPass(),
            // Compiler pass to auto discover Domain Registries handlers.
            new RepositoryCompilerPass(),
            // Compiler pass to auto discover Middleware.
            new MiddlewareCompilerPass(),
        ];
    }
}
