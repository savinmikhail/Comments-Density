<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use PhpParser\Node;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use SavinMikhail\CommentsDensity\DTO\Input\MissingDocblockConfigDTO;

final class MissingDocBlockVisitor extends NodeVisitorAbstract
{
    public array $missingDocBlocks = [];

    public function __construct(
        private readonly string $filename,
        private readonly MissingDocblockConfigDTO $config
    ) {
    }

    public function enterNode(Node $node): void
    {
        if (! $this->requiresDocBlock($node)) {
            return;
        }
        $docComment = $node->getDocComment();
        if ($docComment !== null) {
            return;
        }
        $this->missingDocBlocks[] = [
            'type' => 'missingDocblock',
            'content' => '',
            'file' => $this->filename,
            'line' => $node->getLine(),
        ];
    }

    private function requiresDocBlock(Node $node): bool
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
            if ($this->config->requireDocblocksForAllMethods) {
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
     * here we want to find methods that has uncaught throws statements or their return type will be better
     * described as generic
     */
    private function methodRequiresAdditionalDocBlock(Node $node): bool
    {
        $returnType = $node->getReturnType();

        if ($returnType instanceof Node\Identifier && $returnType->toString() === 'array') {
            return true;
        }

        if ($this->methodThrowsExceptions($node)) {
            return true;
        }

        return false;
    }

    private function methodThrowsExceptions(Node $node): bool
    {
        $traverser = new NodeTraverser();
        $visitor = new class extends NodeVisitorAbstract {
            public bool $throwsExceptions = false;

            public function enterNode(Node $node): void
            {
                if ($node instanceof Throw_) {
                    $this->throwsExceptions = true;
                }
            }
        };
        $traverser->addVisitor($visitor);
        $traverser->traverse([$node]);

        return $visitor->throwsExceptions;
    }
}
