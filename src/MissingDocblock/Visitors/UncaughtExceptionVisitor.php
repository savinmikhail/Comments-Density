<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock\Visitors;

use phpDocumentor\Reflection\DocBlockFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeVisitorAbstract;
use ReflectionClass;

final class UncaughtExceptionVisitor extends NodeVisitorAbstract
{
    private MethodRegistrar $methodRegistrar;
    private ExceptionChecker $exceptionChecker;
    private DocBlockFactory $docBlockFactory;

    public function __construct(?Class_ $class)
    {
        $this->methodRegistrar = new MethodRegistrar($class);
        $this->exceptionChecker = new ExceptionChecker();
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

    public function hasUncaughtException(): bool
    {
        return $this->exceptionChecker->hasUncaughtThrows;
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof ClassMethod) {
            $this->methodRegistrar->registerClassMethod($node);
        }

        if ($node instanceof TryCatch) {
            $this->exceptionChecker->pushTryCatch($node);
        }

        if ($node instanceof Throw_) {
            $this->exceptionChecker->checkIfExceptionIsCaught($node);
        }

        if ($node instanceof MethodCall) {
            $this->checkMethodCallForThrowingUncaughtException($node);
        }

        return null;
    }

    public function leaveNode(Node $node): ?Node
    {
        if ($node instanceof TryCatch) {
            $this->exceptionChecker->popTryCatch();
        }

        return null;
    }

    private function checkMethodCallForThrowingUncaughtException(MethodCall $node): void
    {
        $methodName = $node->name->name;
        if (!isset($node->var->name)) {
            return;
        }

        $objectName = (string) $node->var->name;
        $class = $this->methodRegistrar->getVariableTypes()[$objectName] ?? null;

        if (!$class) {
            return;
        }

        $exceptions = $this->getMethodThrownExceptions($class, $methodName);
        foreach ($exceptions as $exception) {
            $throwNode = new Throw_(new Variable($exception), $node->getAttributes());
            $this->exceptionChecker->checkIfExceptionIsCaught($throwNode);
        }
    }

    private function getMethodThrownExceptions(string $className, string $methodName): array
    {
        if (!class_exists($className)) {
            return [];
        }

        $reflectionClass = new ReflectionClass($className);
        if (!$reflectionClass->hasMethod($methodName)) {
            return [];
        }

        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $docComment = $reflectionMethod->getDocComment();

        if (!$docComment) {
            return [];
        }

        $docBlock = $this->docBlockFactory->create($docComment);

        $exceptions = [];
        foreach ($docBlock->getTagsByName('throws') as $tag) {
            $exceptions[] = (string)$tag->getType();
        }

        return $exceptions;
    }
}
