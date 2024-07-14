<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use SavinMikhail\CommentsDensity\DTO\Input\MissingDocblockConfigDTO;

final readonly class DocBlockChecker
{
    public function __construct(
        private MissingDocblockConfigDTO $config,
        private MethodAnalyzer $methodAnalyzer,
    ) {
    }

    public function requiresDocBlock(Node $node): bool
    {
        if ($node instanceof Class_ && $this->config->class) {
            return !$node->isAnonymous();
        }

        if ($node instanceof Trait_ && $this->config->trait) {
            return true;
        }

        if ($node instanceof Interface_ && $this->config->interface) {
            return true;
        }

        if ($node instanceof Enum_ && $this->config->enum) {
            return true;
        }

        if ($node instanceof Function_ && $this->config->function) {
            return true;
        }

        if ($node instanceof ClassMethod && $this->config->function) {
            if ($this->config->requireForAllMethods) {
                return true;
            }
            return $this->methodRequiresAdditionalDocBlock($node);
        }

        if ($node instanceof Property && $this->config->property) {
            return true;
        }

        if ($node instanceof ClassConst && $this->config->constant) {
            return true;
        }

        return false;
    }

    /**
     * here we want to find methods that have uncaught throw statements or their return type will be better
     * described as generic
     */
    private function methodRequiresAdditionalDocBlock(Node $node): bool
    {
        return
            $this->methodAnalyzer->methodNeedsGeneric($node)
            || $this->methodAnalyzer->methodThrowsUncaughtExceptions($node);
    }
}