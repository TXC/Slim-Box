<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\Resolvers;

use Roave\BetterReflection\Reflection\ReflectionClass;

final class InterfaceResolver extends AbstractResolver
{
    protected function matches(string $lookingFor, ReflectionClass $class): bool
    {
        return $class->implementsInterface($lookingFor);
    }
}
