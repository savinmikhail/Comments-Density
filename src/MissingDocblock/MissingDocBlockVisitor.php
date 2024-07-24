<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;

final class MissingDocBlockVisitor extends NodeVisitorAbstract
{
    /** @var CommentDTO[]  */
    public array $missingDocBlocks = [];

    public function __construct(
        private readonly string $filename,
        private readonly DocBlockChecker $docBlockChecker,
    ) {
    }

    public function enterNode(Node $node): null
    {
        if (! $this->docBlockChecker->requiresDocBlock($node)) {
            return null;
        }
        $docComment = $node->getDocComment();
        if ($docComment !== null) {
            return null;
        }

        $this->missingDocBlocks[] =
            new CommentDTO(
                'missingDocblock', //todo: use methods from MissingDocBlockAnalyzer
                'red',
                $this->filename,
                $node->getLine(),
                $this->docBlockChecker->determineMissingContent(),
            );

        return null;
    }
}
