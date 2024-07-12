<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use SavinMikhail\CommentsDensity\DTO\Input\MissingDocblockConfigDTO;

final class MissingDocBlockVisitor extends NodeVisitorAbstract
{
    public array $missingDocBlocks = [];
    private string $filename;
    private MissingDocblockConfigDTO $config;

    public function __construct(string $filename, MissingDocblockConfigDTO $config)
    {
        $this->filename = $filename;
        $this->config = $config;
    }

    public function enterNode(Node $node): void
    {
        if ($this->requiresDocBlock($node)) {
            $docComment = $node->getDocComment();
            if ($docComment === null) {
                $this->missingDocBlocks[] = [
                    'type' => 'missingDocblock',
                    'content' => '',
                    'file' => $this->filename,
                    'line' => $node->getLine(),
                ];
            }
        }
    }

    private function requiresDocBlock(Node $node): bool
    {
        if ($node instanceof Node\Stmt\Class_ && $this->config->class) {
            return !$node->isAnonymous();
        }

        if ($node instanceof Node\Stmt\Trait_ && $this->config->trait) {
            return true;
        }

        if ($node instanceof Node\Stmt\Interface_ && $this->config->interface) {
            return true;
        }

        if ($node instanceof Node\Stmt\Enum_ && $this->config->enum) {
            return true;
        }

        if ($node instanceof Node\Stmt\Function_ && $this->config->function) {
            return true;
        }

        if ($node instanceof Node\Stmt\ClassMethod && $this->config->function) {
            return true;
        }

        if ($node instanceof Node\Stmt\Property && $this->config->property) {
            return true;
        }

        if ($node instanceof Node\Stmt\ClassConst && $this->config->constant) {
            return true;
        }

        return false;
    }
}
