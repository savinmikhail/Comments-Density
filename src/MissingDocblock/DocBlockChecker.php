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

final class DocBlockChecker
{
    private const MISSING_DOC = 'missing doc';
    private const MISSING_THROWS_TAG = 'missing @throws tag';
    private const MISSING_GENERIC = 'missing generic';

    private bool $needsGeneric = false;
    private bool $throwsUncaught = false;

    public function __construct(
        private readonly MissingDocblockConfigDTO $config,
        private readonly MethodAnalyzer $methodAnalyzer,
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

        if (
            ($node instanceof ClassMethod || $node instanceof Function_)
            && $this->config->function
        ) {
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
    private function methodRequiresAdditionalDocBlock(ClassMethod|Function_ $node): bool
    {
        $this->throwsUncaught = $this->methodAnalyzer->methodThrowsUncaughtExceptions($node);
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
