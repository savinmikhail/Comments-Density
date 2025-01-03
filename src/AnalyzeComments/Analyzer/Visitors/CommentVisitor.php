<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\CommentTypeFactory;

final class CommentVisitor extends NodeVisitorAbstract
{
    /** @var CommentDTO[] */
    public array $comments = [];

    public function __construct(
        private readonly string $filename,
        private readonly CommentTypeFactory $commentFactory,
    ) {}

    public function enterNode(Node $node): null
    {
        $comments = array_filter([$node->getDocComment(), ...$node->getComments()]);
        foreach ($comments as $comment) {
            $commentType = $this->commentFactory->classifyComment($comment->getText());
            if ($commentType === null) {
                continue;
            }
            $this->comments[] =
                new CommentDTO(
                    $commentType->getName(),
                    $commentType->getColor(),
                    $this->filename,
                    $comment->getStartLine(),
                    $comment->getText(),
                );
        }

        return null;
    }
}
