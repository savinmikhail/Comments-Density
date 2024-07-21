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

use function class_exists;
use function in_array;

final readonly class MethodAnalyzer
{
    public function methodNeedsGeneric(ClassMethod|Function_ $node): bool
    {
        $returnType = $node->getReturnType();

        if ($returnType === null) {
            return false;
        }

        if (
            $returnType instanceof Identifier
            && in_array($returnType->toString(), ['array', 'iterable'], true)
        ) {
            return $this->arrayElementsHaveConsistentTypes($node);
        }

        if (
            $returnType instanceof Name
            && in_array($returnType->toString(), ['Generator', 'Traversable', 'Iterator', 'ArrayAccess'], true)
        ) {
            return $this->arrayElementsHaveConsistentTypes($node);
        }

        if ($this->isReturnClassTraversable($returnType)) {
            return $this->arrayElementsHaveConsistentTypes($node);
        }

        return false;
    }

    private function isReturnClassTraversable(ComplexType|Identifier|Name $returnType): bool
    {
        if (!($returnType instanceof Name)) {
            return false;
        }

        $returnTypeName = $returnType->toString();
        if (!class_exists($returnTypeName)) {
            return false;
        }
        $reflectionClass = new ReflectionClass($returnTypeName);
        if (
            ! $reflectionClass->implementsInterface(Iterator::class)
            && !$reflectionClass->implementsInterface(ArrayAccess::class)
        ) {
            return false;
        }
        return true;
    }

    private function arrayElementsHaveConsistentTypes(Node $node): bool
    {
        $traverser = new NodeTraverser();
        $visitor = new MissingGenericVisitor();

        $traverser->addVisitor($visitor);
        $traverser->traverse([$node]);

        return $visitor->hasConsistentTypes;
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
