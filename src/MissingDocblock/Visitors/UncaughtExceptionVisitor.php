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

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof ClassMethod) {
            if ($this->class) {
                // Register the type of $this
                $className = $this->class->namespacedName->toString();
                $this->variableTypes['this'] = $className;
            }
            if ($node->getStmts()) {
                foreach ($node->getStmts() as $stmt) {
                    if ($stmt instanceof Expression && $stmt->expr instanceof Assign) {
                        $var = $stmt->expr->var;
                        $expr = $stmt->expr->expr;
                        if ($var instanceof Variable && $expr instanceof New_) {
                            $this->variableTypes[$var->name] = $expr->class->name;
                        }
                    }
                }
            }
        }

        if ($node instanceof TryCatch) {
            $this->tryCatchStack[] = $node;
        }

        if ($node instanceof Throw_) {
            if (!$this->isInTryBlock($node)) {
                $this->hasUncaughtThrows = true;
            } elseif (!$this->isExceptionCaught($node)) {
                $this->hasUncaughtThrows = true;
            } elseif ($this->isInCatchBlock($node) && !$this->isRethrowingCaughtException($node)) {
                $this->hasUncaughtThrows = true;
            }
        }

        if ($node instanceof MethodCall) {
            $methodName = $node->name->name;
            if (isset($node->var->name)) {
                $objectName = (string) $node->var->name;
            }

            if (isset($objectName)) {
                $class = $this->variableTypes[$objectName] ?? null;

                if ($class) {
                    $exceptions = $this->getMethodThrownExceptions($class, $methodName);
                    foreach ($exceptions as $exception) {
                        $throwNode = new Throw_(new Variable($exception), $node->getAttributes());

                        if (!$this->isInTryBlock($throwNode)) {
                            $this->hasUncaughtThrows = true;
                        } elseif (!$this->isExceptionCaught($throwNode)) {
                            $this->hasUncaughtThrows = true;
                        } elseif ($this->isInCatchBlock($throwNode) && !$this->isRethrowingCaughtException($throwNode)) {
                            $this->hasUncaughtThrows = true;
                        }
                    }
                }
            }
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

    private function isExceptionCaught(Throw_ $throwNode): bool
    {
        $throwExpr = $throwNode->expr;

        if ($throwExpr instanceof Variable) {
            $thrownExceptionType = $throwExpr->name;
        } elseif ($throwExpr instanceof New_) {
            $thrownExceptionType = $throwExpr->class->name;
        } else {
            return false;
        }

        foreach ($this->getCurrentCatchStack() as $catch) {
            foreach ($catch->types as $catchType) {
                $catchTypeName = $catchType->name;
                if (!$catchType->isQualified()) {
                    $catchTypeName = '\\' . $catchTypeName;
                }
                if ($thrownExceptionType[0] !== '\\') {
                    $thrownExceptionType = '\\' . $thrownExceptionType;
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
