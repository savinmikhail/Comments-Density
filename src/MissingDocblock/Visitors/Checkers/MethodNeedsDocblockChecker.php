<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock\Visitors\Checkers;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeTraverser;
use SavinMikhail\CommentsDensity\MissingDocblock\Visitors\UncaughtExceptionVisitor;

final readonly class MethodNeedsDocblockChecker
{
    private TemplateChecker $templateChecker;

    private TraversableChecker $traversableChecker;

    public function __construct()
    {
        $this->templateChecker = new TemplateChecker();
        $this->traversableChecker = new TraversableChecker();
    }

    public function methodNeedsGeneric(ClassMethod|Function_ $node): bool
    {
        $returnType = $node->getReturnType();

        if ($this->traversableChecker->isTypeIterable($returnType)) {
            return true;
        }

        foreach ($node->getParams() as $param) {
            if (
                $this->traversableChecker->isTypeIterable($param->type)
                || $this->templateChecker->isTemplatedClass($param->type)
            ) {
                return true;
            }
        }

        return false;
    }

    public function methodNeedsThrowsTag(ClassMethod|Function_ $node, ?Class_ $class): bool
    {
        $traverser = new NodeTraverser();
        $visitor = new UncaughtExceptionVisitor($class);

        $traverser->addVisitor($visitor);
        $traverser->traverse([$node]);

        return $visitor->hasUncaughtException();
    }
}
