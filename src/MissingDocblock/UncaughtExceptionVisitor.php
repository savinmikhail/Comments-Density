<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use PhpParser\Node;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeVisitorAbstract;

final class UncaughtExceptionVisitor extends NodeVisitorAbstract
{
    public bool $throwsUncaughtExceptions = false;
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
            if (!$this->isInCatchBlock($node) && !$this->isInTryBlock($node)) {
                $this->throwsUncaughtExceptions = true;
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

    private function nodeIsWithin(Node $node, Node $container): bool
    {
        return $node->getStartFilePos() >= $container->getStartFilePos() &&
            $node->getEndFilePos() <= $container->getEndFilePos();
    }
}
