<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\MissingDocblock;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\MissingDocblock\Visitors\Checkers\NodeNeedsDocblockChecker;
use SavinMikhail\CommentsDensity\AnalyzeComments\MissingDocblock\Visitors\MissingDocBlockVisitor;

final class MissingDocBlockAnalyzer
{
    public const NAME = 'missingDocblock';
    public const COLOR = 'red';

    private bool $exceedThreshold = false;

    private readonly Parser $parser;

    public function __construct(
        private readonly MissingDocblockConfigDTO $docblockConfigDTO,
        ?Parser $parser = null,
    ) {
        $this->parser = $parser ?? (new ParserFactory())->createForHostVersion();
    }

    /**
     * @return CommentDTO[]
     */
    public function analyze(string $code, string $filename): array
    {
        $traverser = new NodeTraverser();

        $nameResolverVisitor = new NameResolver();
        $traverser->addVisitor($nameResolverVisitor);

        $missingDocBlockVisitor = new MissingDocBlockVisitor(
            $filename,
            new NodeNeedsDocblockChecker($this->docblockConfigDTO),
        );
        $traverser->addVisitor($missingDocBlockVisitor);

        $traverser->traverse($this->parser->parse($code));

        return $missingDocBlockVisitor->missingDocBlocks;
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
