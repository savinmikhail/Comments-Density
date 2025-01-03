<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\MissingDocblock\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\MissingDocblock\MissingDocBlockAnalyzer;
use SavinMikhail\CommentsDensity\AnalyzeComments\MissingDocblock\Visitors\Checkers\NodeNeedsDocblockChecker;

final class MissingDocBlockVisitor extends NodeVisitorAbstract
{
    /** @var CommentDTO[] */
    public array $missingDocBlocks = [];

    public function __construct(
        private readonly string $filename,
        private readonly NodeNeedsDocblockChecker $nodeChecker,
    ) {}

    public function enterNode(Node $node): null
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
                MissingDocBlockAnalyzer::NAME, // todo
                MissingDocBlockAnalyzer::COLOR,
                $this->filename,
                $node->getLine(),
                '',
            );

        return null;
    }
}
