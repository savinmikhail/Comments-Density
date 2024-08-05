<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock\Visitors\Checkers;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\TryCatch;
use ReflectionClass;

final class ExceptionChecker
{
    private array $tryCatchStack = [];
    public bool $hasUncaughtThrows = false;

    public function checkIfExceptionIsCaught(Throw_ $node): void
    {
        if (!$this->isInTryBlock($node)) {
            $this->hasUncaughtThrows = true;
        } elseif (!$this->isExceptionCaught($node)) {
            $this->hasUncaughtThrows = true;
        } elseif ($this->isInCatchBlock($node)) {
            $this->hasUncaughtThrows = true;
        }
    }

    public function pushTryCatch(TryCatch $node): void
    {
        $this->tryCatchStack[] = $node;
    }

    public function popTryCatch(): void
    {
        array_pop($this->tryCatchStack);
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
