<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\MissingDocblock\MissingDocBlockAnalyzer;
use SavinMikhail\CommentsDensity\MissingDocblock\Visitors\Checkers\NodeNeedsDocblockChecker;

final class MissingDocBlockVisitor extends NodeVisitorAbstract
{
    /**
     * @readonly
     */
    private string $filename;
    /**
     * @readonly
     */
    private NodeNeedsDocblockChecker $nodeChecker;
    /** @var CommentDTO[]  */
    public array $missingDocBlocks = [];

    public function __construct(string $filename, NodeNeedsDocblockChecker $nodeChecker)
    {
        $this->filename = $filename;
        $this->nodeChecker = $nodeChecker;
    }

    /**
     * @return null
     */
    public function enterNode(Node $node)
    {
        if (! $this->nodeChecker->requiresDocBlock($node)) {
            return null;
        }
        $docComment = $node->getDocComment();
        if ($docComment !== null) {
            return null;
        }

        $this->missingDocBlocks[] =
            new CommentDTO(
                MissingDocBlockAnalyzer::NAME, //todo: use methods from MissingDocBlockAnalyzer
                MissingDocBlockAnalyzer::COLOR,
                $this->filename,
                $node->getLine(),
                $this->nodeChecker->determineMissingContent(),
            );

        return null;
    }
}
