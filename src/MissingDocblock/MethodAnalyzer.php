<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeTraverser;

use function class_exists;
use function in_array;

final readonly class MethodAnalyzer
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

        return $this->isClassTraversable($type);
    }

    private function isClassTraversable(ComplexType|Identifier|Name $returnType): bool
    {
        if (!($returnType instanceof Name)) {
            return false;
        }

        $returnTypeName = $returnType->toString();
        if (!class_exists($returnTypeName)) {
            return false;
        }

        $reflectionClass = new \ReflectionClass($returnTypeName);
        return $reflectionClass->implementsInterface(\Iterator::class)
            || $reflectionClass->implementsInterface(\ArrayAccess::class);
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
