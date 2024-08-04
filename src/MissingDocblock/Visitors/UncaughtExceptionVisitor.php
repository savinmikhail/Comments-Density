<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock\Visitors;

use phpDocumentor\Reflection\DocBlockFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeVisitorAbstract;
use ReflectionClass;

use function array_pop;
use function class_exists;

final class UncaughtExceptionVisitor extends NodeVisitorAbstract
{
    public bool $hasUncaughtThrows = false;
    /**
     * @var TryCatch[]
     */
    private array $tryCatchStack = [];

    private array $variableTypes = [];

    public function __construct(private readonly ?Class_ $class)
    {
    }

    private function registerClassMethod(ClassMethod $node): void
    {
        if ($this->class) {
            $className = $this->class->namespacedName->toString();
            $this->variableTypes['this'] = $className;
        }
        $stmts = $node->getStmts();
        if (!$stmts) {
            return;
        }
        foreach ($stmts as $stmt) {
            if (!($stmt instanceof Expression && $stmt->expr instanceof Assign)) {
                continue;
            }
            $var = $stmt->expr->var;
            $expr = $stmt->expr->expr;
            if ($var instanceof Variable && $expr instanceof New_) {
                $this->variableTypes[$var->name] = $expr->class->name;
            }
        }
    }

    private function checkIfExceptionIsCaught(Throw_ $node): void
    {
        if (!$this->isInTryBlock($node)) {
            $this->hasUncaughtThrows = true;
        } elseif (!$this->isExceptionCaught($node)) {
            $this->hasUncaughtThrows = true;
        } elseif ($this->isInCatchBlock($node) && !$this->isRethrowingCaughtException($node)) {
            $this->hasUncaughtThrows = true;
        }
    }

    private function checkMethodCallForThrowingUncaughtException(MethodCall $node): void
    {
        $methodName = $node->name->name;
        if (!isset($node->var->name)) {
            return;
        }

        $objectName = (string) $node->var->name;

        $class = $this->variableTypes[$objectName] ?? null;

        if (!$class) {
            return;
        }

        $exceptions = $this->getMethodThrownExceptions($class, $methodName);
        foreach ($exceptions as $exception) {
            $throwNode = new Throw_(new Variable($exception), $node->getAttributes());
            $this->checkIfExceptionIsCaught($throwNode);
        }
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof ClassMethod) {
            $this->registerClassMethod($node);
        }

        if ($node instanceof TryCatch) {
            $this->tryCatchStack[] = $node;
        }

        if ($node instanceof Throw_) {
            $this->checkIfExceptionIsCaught($node);
        }

        if ($node instanceof MethodCall) {
            $this->checkMethodCallForThrowingUncaughtException($node);
        }

        return null;
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

        $docBlockFactory = DocBlockFactory::createInstance();
        $docBlock = $docBlockFactory->create($docComment);

        $exceptions = [];
        foreach ($docBlock->getTagsByName('throws') as $tag) {
            $exceptions[] = (string)$tag->getType();
        }

        return $exceptions;
    }

    public function leaveNode(Node $node): ?Node
    {
        if ($node instanceof TryCatch) {
            array_pop($this->tryCatchStack);
        }

        return null;
    }

    private function isInCatchBlock(Throw_ $node): bool
    {
        foreach ($this->getCurrentCatchStack() as $catch) {
            if ($this->nodeIsWithin($node, $catch)) {
                return true;
            }
        }
        return false;
    }

    private function isInTryBlock(Throw_ $node): bool
    {
        foreach ($this->tryCatchStack as $tryCatch) {
            foreach ($tryCatch->stmts as $stmt) {
                if ($this->nodeIsWithin($node, $stmt)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function isRethrowingCaughtException(Throw_ $throwNode): bool
    {
        $throwExpr = $throwNode->expr;

        if (!$throwExpr instanceof Variable) {
            return false;
        }

        foreach ($this->getCurrentCatchStack() as $catch) {
            if ($catch->var instanceof Variable && $catch->var->name === $throwExpr->name) {
                return true;
            }
        }

        return false;
    }

    private function nodeIsWithin(Node $node, Node $container): bool
    {
        return $node->getStartFilePos() >= $container->getStartFilePos() &&
            $node->getEndFilePos() <= $container->getEndFilePos();
    }

    private function getExceptionFQN(Throw_ $throwNode): string
    {
        $throwExpr = $throwNode->expr;

        if ($throwExpr instanceof Variable) {
            $thrownExceptionType = $throwExpr->name;
        } elseif ($throwExpr instanceof New_) {
            $thrownExceptionType = $throwExpr->class->name;
        }

        if ($thrownExceptionType[0] !== '\\') {
            $thrownExceptionType = '\\' . $thrownExceptionType;
        }

        return $thrownExceptionType;
    }

    private function isExceptionCaught(Throw_ $throwNode): bool
    {
        $thrownExceptionType = $this->getExceptionFQN($throwNode);

        foreach ($this->getCurrentCatchStack() as $catch) {
            foreach ($catch->types as $catchType) {
                $catchTypeName = $catchType->name;
                if (!$catchType->isQualified()) {
                    $catchTypeName = '\\' . $catchTypeName;
                }

                if (
                    $this->isSubclassOf($thrownExceptionType, $catchTypeName)
                    || $catchType->name === 'Throwable'
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return Catch_[] */
    private function getCurrentCatchStack(): array
    {
        $catchStack = [];
        foreach ($this->tryCatchStack as $tryCatch) {
            $catchStack = array_merge($catchStack, $tryCatch->catches);
        }
        return $catchStack;
    }

    private function isSubclassOf(?string $className, string $parentClassName): bool
    {
        if ($className === null) {
            return false;
        }

        if (!class_exists($className) || !class_exists($parentClassName)) {
            return false;
        }

        $reflectionClass = new ReflectionClass($className);
        return $reflectionClass->isSubclassOf($parentClassName) || $className === $parentClassName;
    }
}
