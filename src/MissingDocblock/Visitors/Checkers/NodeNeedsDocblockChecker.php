<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock\Visitors\Checkers;

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

final class NodeNeedsDocblockChecker
{
    /**
     * @readonly
     */
    private MissingDocblockConfigDTO $config;
    /**
     * @readonly
     */
    private MethodNeedsDocblockChecker $methodAnalyzer;
    private const MISSING_DOC = 'missing doc';
    private const MISSING_THROWS_TAG = 'missing @throws tag';
    private const MISSING_GENERIC = 'missing generic';

    private bool $needsGeneric = false;
    private bool $throwsUncaught = false;

    private ?Class_ $class = null;

    public function __construct(MissingDocblockConfigDTO $config, MethodNeedsDocblockChecker $methodAnalyzer)
    {
        $this->config = $config;
        $this->methodAnalyzer = $methodAnalyzer;
    }

    public function requiresDocBlock(Node $node): bool
    {
        if ($node instanceof Class_) {
            $this->class = $node;
            return $this->config->class && !$node->isAnonymous();
        }

        if ($this->isConfiguredNode($node)) {
            return true;
        }

        if ($this->isMethodOrFunction($node)) {
            /** @var ClassMethod|Function_ $node */
            return $this->requiresMethodOrFunctionDocBlock($node);
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

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_ $node
     */
    private function requiresMethodOrFunctionDocBlock($node): bool
    {
        if ($this->config->requireForAllMethods) {
            return true;
        }
        return $this->methodRequiresAdditionalDocBlock($node);
    }

    /**
     * here we want to find methods that have uncaught throw statements or their return type will be better
     * described as generic
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_ $node
     */
    private function methodRequiresAdditionalDocBlock($node): bool
    {
        $this->throwsUncaught = $this->methodAnalyzer->methodNeedsThrowsTag($node, $this->class);
        $this->needsGeneric = $this->methodAnalyzer->methodNeedsGeneric($node);

        return $this->throwsUncaught || $this->needsGeneric;
    }

    public function determineMissingContent(): string
    {
        if ($this->needsGeneric && $this->throwsUncaught) {
            $this->needsGeneric = false;
            $this->throwsUncaught = false;
            return self::MISSING_THROWS_TAG . ' and ' . self::MISSING_GENERIC;
        }

        if ($this->needsGeneric) {
            $this->needsGeneric = false;
            return self::MISSING_GENERIC;
        }

        if ($this->throwsUncaught) {
            $this->throwsUncaught = false;
            return self::MISSING_THROWS_TAG;
        }

        return self::MISSING_DOC;
    }
}
