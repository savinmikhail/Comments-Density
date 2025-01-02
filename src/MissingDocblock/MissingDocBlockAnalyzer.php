<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\Config\DTO\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\MissingDocblock\Visitors\Checkers\NodeNeedsDocblockChecker;
use SavinMikhail\CommentsDensity\MissingDocblock\Visitors\MissingDocBlockVisitor;

final class MissingDocBlockAnalyzer
{
    public const NAME = 'missingDocblock';
    public const COLOR = 'red';

    private bool $exceedThreshold = false;

    public function __construct(
        private readonly MissingDocblockConfigDTO $docblockConfigDTO,
    ) {}

    /**
     * Analyzes the AST of a file for missing docblocks.
     *
     * @param string $code the code to analyze
     * @param string $filename the filename of the code
     *
     * @return CommentDTO[] the analysis results
     */
    public function analyze(string $code, string $filename): array
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $ast = $parser->parse($code);
        $traverser = new NodeTraverser();

        $nameResolver = new NameResolver();
        $traverser->addVisitor($nameResolver);
        $fqnNodes = $traverser->traverse($ast);

        $visitor = new MissingDocBlockVisitor(
            $filename,
            new NodeNeedsDocblockChecker($this->docblockConfigDTO),
        );
        $traverser->removeVisitor($nameResolver);
        $traverser->addVisitor($visitor);
        $traverser->traverse($fqnNodes);

        return $visitor->missingDocBlocks;
    }

    /**
     * @return CommentDTO[]
     */
    public function getMissingDocblocks(string $code, string $filename): array
    {
        return $this->analyze($code, $filename);
    }

    public function getColor(): string
    {
        return self::COLOR;
    }

    /**
     * @param array<string, float> $thresholds
     */
    public function getStatColor(float $count, array $thresholds): string
    {
        if (!isset($thresholds['missingDocBlock'])) {
            return 'white';
        }
        if ($count <= $thresholds['missingDocBlock']) {
            return 'green';
        }
        $this->exceedThreshold = true;

        return 'red';
    }

    public function hasExceededThreshold(): bool
    {
        return $this->exceedThreshold;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
