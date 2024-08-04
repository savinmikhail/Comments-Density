<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock\Visitors\Checkers;

use ArrayAccess;
use Iterator;
use phpDocumentor\Reflection\DocBlockFactory;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeTraverser;
use ReflectionClass;
use SavinMikhail\CommentsDensity\MissingDocblock\Visitors\ExceptionChecker;
use SavinMikhail\CommentsDensity\MissingDocblock\Visitors\MethodRegistrar;
use SavinMikhail\CommentsDensity\MissingDocblock\Visitors\UncaughtExceptionVisitor;
use Traversable;

use function class_exists;
use function dd;
use function in_array;
use function interface_exists;

final class MethodNeedsDocblockChecker
{
    /**
     * @readonly
     */
    private DocBlockFactory $docBlockFactory;

    public function __construct()
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_ $node
     */
    public function methodNeedsGeneric($node): bool
    {
        $returnType = $node->getReturnType();

        if ($this->isTypeIterable($returnType)) {
            return true;
        }

        foreach ($node->getParams() as $param) {
            if ($this->isTypeIterable($param->type) || $this->isTemplatedClass($param->type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_ $node
     */
    public function methodNeedsThrowsTag($node, ?Class_ $class): bool
    {
        $traverser = new NodeTraverser();
        $visitor = new UncaughtExceptionVisitor($class);

        $traverser->addVisitor($visitor);
        $traverser->traverse([$node]);

        return $visitor->hasUncaughtException();
    }

    /**
     * @param \PhpParser\Node\ComplexType|\PhpParser\Node\Identifier|\PhpParser\Node\Name|null $type
     */
    private function isTypeIterable($type): bool
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

    /**
     * @param \PhpParser\Node\ComplexType|\PhpParser\Node\Identifier|\PhpParser\Node\Name $type
     */
    private function isClassOrInterfaceTraversable($type): bool
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

    /**
     * @param ReflectionClass<object> $reflection
     * @return bool
     */
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

    /**
     * @param \PhpParser\Node\ComplexType|\PhpParser\Node\Identifier|\PhpParser\Node\Name|null $type
     */
    private function isTemplatedClass($type): bool
    {
        if ($type === null) {
            return false;
        }
        if (!($type instanceof Name)) {
            return false;
        }

        $typeName = $type->toString();

        if (!class_exists($typeName) && !interface_exists($typeName)) {
            return false;
        }

        $class = new ReflectionClass($typeName);
        $docComment = $class->getDocComment();

        if (!$docComment) {
            return false;
        }

        $docBlock = $this->docBlockFactory->create($docComment);

        return !empty($docBlock->getTagsByName('template'));
    }
}
