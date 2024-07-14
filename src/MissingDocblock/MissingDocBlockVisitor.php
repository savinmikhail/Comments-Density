<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use ArrayAccess;
use Iterator;
use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
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
use ReflectionClass;
use SavinMikhail\CommentsDensity\DTO\Input\MissingDocblockConfigDTO;

use function class_exists;
use function in_array;

final class MissingDocBlockVisitor extends NodeVisitorAbstract
{
    public array $missingDocBlocks = [];

    public function __construct(
        private readonly string $filename,
        private readonly DocBlockChecker $docBlockChecker,
    ) {
    }

    public function enterNode(Node $node): void
    {
        if (! $this->docBlockChecker->requiresDocBlock($node)) {
            return;
        }
        $docComment = $node->getDocComment();
        if ($docComment !== null) {
            return;
        }

        $this->missingDocBlocks[] = [
            'type' => 'missingDocblock',
            'content' => $this->docBlockChecker->determineMissingContent(),
            'file' => $this->filename,
            'line' => $node->getLine(),
        ];
    }
}
