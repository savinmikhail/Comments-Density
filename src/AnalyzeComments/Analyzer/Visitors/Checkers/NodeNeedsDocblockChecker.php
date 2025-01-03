<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\Visitors\Checkers;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\MissingDocblockConfigDTO;

final readonly class NodeNeedsDocblockChecker
{
    public function __construct(
        private MissingDocblockConfigDTO $config,
    ) {}

    public function requiresDocBlock(Node $node): bool
    {
        if ($node instanceof Class_) {
            return $this->config->class && !$node->isAnonymous();
        }

        if ($this->isConfiguredNode($node)) {
            return true;
        }

        if ($this->isMethodOrFunction($node)) {
            return true;
        }

        return false;
    }

    private function isConfiguredNode(Node $node): bool
    {
        return $this->isConfiguredTrait($node)
            || $this->isConfiguredInterface($node)
            || $this->isConfiguredEnum($node)
            || $this->isConfiguredProperty($node)
            || $this->isConfiguredConstant($node);
    }

    private function isConfiguredTrait(Node $node): bool
    {
        return $node instanceof Trait_ && $this->config->trait;
    }

    private function isConfiguredInterface(Node $node): bool
    {
        return $node instanceof Interface_ && $this->config->interface;
    }

    private function isConfiguredEnum(Node $node): bool
    {
        return $node instanceof Enum_ && $this->config->enum;
    }

    private function isConfiguredProperty(Node $node): bool
    {
        return $node instanceof Property && $this->config->property;
    }

    private function isConfiguredConstant(Node $node): bool
    {
        return $node instanceof ClassConst && $this->config->constant;
    }

    private function isMethodOrFunction(Node $node): bool
    {
        return ($node instanceof ClassMethod || $node instanceof Function_) && $this->config->function;
    }
}
