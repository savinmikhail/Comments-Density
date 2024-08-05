<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock\Visitors\Checkers;

use ArrayAccess;
use Iterator;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use ReflectionClass;
use Traversable;

use function class_exists;
use function in_array;
use function interface_exists;

final readonly class TraversableChecker
{
    public function isTypeIterable(ComplexType|Identifier|Name|null $type): bool
    {
        if ($type === null) {
            return false;
        }

        if (
            $type instanceof Identifier
            && in_array($type->toString(), ['array', 'iterable'], true)
        ) {
            return true;
        }

        if (
            $type instanceof Name
            && in_array($type->toString(), ['Generator', 'Traversable', 'Iterator', 'ArrayAccess'], true)
        ) {
            return true;
        }

        return $this->isClassOrInterfaceTraversable($type);
    }

    private function isClassOrInterfaceTraversable(ComplexType|Identifier|Name $type): bool
    {
        if (!($type instanceof Name)) {
            return false;
        }

        $typeName = $type->toString();

        if (!class_exists($typeName) && !interface_exists($typeName)) {
            return false;
        }

        return $this->isTraversableRecursively(new ReflectionClass($typeName));
    }


    private function isTraversableRecursively(ReflectionClass $reflection): bool
    {
        if (
            $reflection->implementsInterface(Iterator::class)
            || $reflection->implementsInterface(ArrayAccess::class)
            || $reflection->implementsInterface(Traversable::class)
        ) {
            return true;
        }

        foreach ($reflection->getInterfaces() as $interface) {
            if ($this->isTraversableRecursively($interface)) {
                return true;
            }
        }

        $parentClass = $reflection->getParentClass();
        if ($parentClass !== false) {
            return $this->isTraversableRecursively($parentClass);
        }

        return false;
    }
}
