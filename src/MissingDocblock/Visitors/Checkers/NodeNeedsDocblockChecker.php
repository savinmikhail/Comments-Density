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
    private const MISSING_DOC = 'missing doc';
    private const MISSING_THROWS_TAG = 'missing @throws tag';
    private const MISSING_GENERIC = 'missing generic';

    private bool $needsGeneric = false;
    private bool $throwsUncaught = false;

    private ?Class_ $class = null;

    public function __construct(
        private readonly MissingDocblockConfigDTO $config,
        private readonly MethodNeedsDocblockChecker $methodAnalyzer,
    ) {
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
        return ($node instanceof Trait_ && $this->config->trait)
            || ($node instanceof Interface_ && $this->config->interface)
            || ($node instanceof Enum_ && $this->config->enum)
            || ($node instanceof Property && $this->config->property)
            || ($node instanceof ClassConst && $this->config->constant);
    }

    private function isMethodOrFunction(Node $node): bool
    {
        return ($node instanceof ClassMethod || $node instanceof Function_) && $this->config->function;
    }

    private function requiresMethodOrFunctionDocBlock(ClassMethod|Function_ $node): bool
    {
        if ($this->config->requireForAllMethods) {
            return true;
        }
        return $this->methodRequiresAdditionalDocBlock($node);
    }

    /**
     * here we want to find methods that have uncaught throw statements or their return type will be better
     * described as generic
     */
    private function methodRequiresAdditionalDocBlock(ClassMethod|Function_ $node): bool
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
