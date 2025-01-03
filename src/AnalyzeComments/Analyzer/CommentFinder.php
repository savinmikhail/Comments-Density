<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer;

use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\Visitors\Checkers\NodeNeedsDocblockChecker;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\Visitors\CommentVisitor;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\Visitors\MissingDocBlockVisitor;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\CommentTypeFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConfigDTO;

use function in_array;

final readonly class CommentFinder
{
    private Parser $parser;

    public function __construct(
        private CommentTypeFactory $commentFactory,
        private ConfigDTO $configDTO,
        ?Parser $parser = null,
    ) {
        $this->parser = $parser ?? (new ParserFactory())->createForHostVersion();
    }

    /**
     * @return CommentDTO[]
     */
    public function __invoke(string $content, string $filename): array
    {
        $traverser = new NodeTraverser();

        $missingDocBlockVisitor = new MissingDocBlockVisitor(
            $filename,
            new NodeNeedsDocblockChecker($this->configDTO->docblockConfigDTO),
        );
        if (in_array('missingDocBlock', $this->configDTO->getAllowedTypes(), true)) {
            $traverser->addVisitor($missingDocBlockVisitor);
        }

        $commentVisitor = new CommentVisitor(
            $filename,
            $this->commentFactory,
        );
        $traverser->addVisitor($commentVisitor);

        $traverser->traverse($this->parser->parse($content));

        return [...$missingDocBlockVisitor->missingDocBlocks, ...$commentVisitor->comments];
    }
}
