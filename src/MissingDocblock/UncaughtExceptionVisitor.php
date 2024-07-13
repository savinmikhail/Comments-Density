<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use PhpParser\Node;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeVisitorAbstract;
use ReflectionClass;
use function dd;

final class UncaughtExceptionVisitor extends NodeVisitorAbstract
{
    public bool $hasUncaughtThrows = false;
    private array $tryCatchStack = [];
    private array $catchStack = [];

    public function enterNode(Node $node): void
    {
        if ($node instanceof TryCatch) {
            $this->tryCatchStack[] = $node;
        }

        if ($node instanceof Catch_) {
            $this->catchStack[] = $node;
        }

        if ($node instanceof Throw_) {
            if (!$this->isInTryBlock($node)) {
                $this->hasUncaughtThrows = true;
            } elseif (!$this->isExceptionCaught($node)) {
                $this->hasUncaughtThrows = true;
            } elseif ($this->isInCatchBlock($node) && $this->isRethrowingCaughtException($node)) {
                $this->hasUncaughtThrows = true;
            }
        }
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof TryCatch) {
            array_pop($this->tryCatchStack);
        }

        if ($node instanceof Catch_) {
            array_pop($this->catchStack);
        }
    }

    private function isInCatchBlock(Node $node): bool
    {
        foreach ($this->catchStack as $catch) {
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
        // Get the throw expression (e.g., `throw $e`)
        $throwExpr = $throwNode->expr;

        // Ensure throwExpr is a variable and has a name
        if (!$throwExpr instanceof Variable || !isset($throwExpr->name)) {
            return false;
        }

        // Check if the throw expression matches any variable from the catch blocks
        foreach ($this->catchStack as $catch) {
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
        if (!($throwExpr instanceof Variable)) {
            return false;
        }

        $thrownExceptionType = $this->getVariableType($throwExpr->name);

        foreach ($this->catchStack as $catch) {
            foreach ($catch->types as $catchType) {
                if ($this->isSubclassOf($thrownExceptionType, (string) $catchType)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getVariableType(string $varName): ?string
    {
        // For simplicity, assume that the variable name matches the class name of the exception
        // In a real-world scenario, you would need to analyze the code to determine the actual type
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
