<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use PhpParser\Node;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeVisitorAbstract;
use ReflectionClass;

final class UncaughtExceptionVisitor extends NodeVisitorAbstract
{
    public bool $hasUncaughtThrows = false;
    private array $tryCatchStack = [];

    public function enterNode(Node $node): void
    {
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
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof TryCatch) {
            array_pop($this->tryCatchStack);
        }
    }

    private function isInCatchBlock(Node $node): bool
    {
        foreach ($this->getCurrentCatchStack() as $catch) {
            if ($this->nodeIsWithin($node, $catch)) {
                return true;
            }
        }
        return false;
    }

    private function isInTryBlock(Node $node): bool
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

    private function isRethrowingCaughtException(Node $throwNode): bool
    {
        $throwExpr = $throwNode->expr;

        if (!$throwExpr instanceof Variable || !isset($throwExpr->name)) {
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

    private function isExceptionCaught(Node $throwNode): bool
    {
        $throwExpr = $throwNode->expr;

        if ($throwExpr instanceof Variable) {
            $thrownExceptionType = $this->getVariableType($throwExpr->name);
        } elseif ($throwExpr instanceof New_) {
            $thrownExceptionType = (string)$throwExpr->class;
        } else {
            return false;
        }

        foreach ($this->getCurrentCatchStack() as $catch) {
            foreach ($catch->types as $catchType) {
                if ($this->isSubclassOf($thrownExceptionType, (string)$catchType) || (string)$catchType === 'Throwable') {
                    return true;
                }
            }
        }

        return false;
    }

    private function getCurrentCatchStack(): array
    {
        $catchStack = [];
        foreach ($this->tryCatchStack as $tryCatch) {
            $catchStack = array_merge($catchStack, $tryCatch->catches);
        }
        return $catchStack;
    }

    private function getVariableType(string $varName): ?string
    {
        return $varName;
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
