<?php

namespace TXC\Box\Infrastructure\Resolvers;

use Roave\BetterReflection\Reflection\ReflectionClass;

final class ClassAttributeResolver extends AbstractResolver
{
    protected function matches(string $lookingFor, ReflectionClass $class): bool
    {
        return !empty($class->getAttributesByName($lookingFor));
    }
}
