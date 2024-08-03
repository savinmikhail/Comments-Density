<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use ArrayAccess;
use Iterator;
use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeTraverser;
use ReflectionClass;
use Traversable;

use function class_exists;
use function in_array;
use function interface_exists;

final class MethodAnalyzer
{
    public function methodNeedsGeneric(ClassMethod|Function_ $node): bool
    {
        $returnType = $node->getReturnType();

        if ($this->isTypeIterable($returnType)) {
            return true;
        }

        foreach ($node->getParams() as $param) {
            if ($this->isTypeIterable($param->type)) {
                return true;
            }
        }

        return false;
    }

    private function isTypeIterable(ComplexType|Identifier|Name|null $type): bool
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
        if ($reflection->implementsInterface(Iterator::class)
            || $reflection->implementsInterface(ArrayAccess::class)
            || $reflection->implementsInterface(Traversable::class)) {
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

    public function methodThrowsUncaughtExceptions(Node $node): bool
    {
        $traverser = new NodeTraverser();
        $visitor = new UncaughtExceptionVisitor();

        $traverser->addVisitor($visitor);
        $traverser->traverse([$node]);

        return $visitor->hasUncaughtThrows;
    }
}
