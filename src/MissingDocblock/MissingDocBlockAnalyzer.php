<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitor\NameResolver;
use SavinMikhail\CommentsDensity\DTO\Input\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;

final class MissingDocBlockAnalyzer
{
    private bool $exceedThreshold = false;

    public function __construct(
        private readonly MissingDocblockConfigDTO $docblockConfigDTO,
    ) {
    }

    /**
     * Analyzes the AST of a file for missing docblocks.
     *
     * @param string $code The code to analyze.
     * @param string $filename The filename of the code.
     *
     * @return CommentDTO[] The analysis results.
     */
    public function analyze(string $code, string $filename): array
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $ast = $parser->parse($code);

        $traverser = new NodeTraverser();
        $visitor = new MissingDocBlockVisitor(
            $filename,
            new DocBlockChecker($this->docblockConfigDTO, new MethodAnalyzer())
        );

        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->missingDocBlocks;
    }

    /**
     * @param string $code
     * @param string $filename
     *
     * @return CommentDTO[]
     */
    public function getMissingDocblocks(string $code, string $filename): array
    {
        return $this->analyze($code, $filename);
    }

    public function getColor(): string
    {
        return 'red';
    }

    /**
     * @param float $count
     * @param array<string, float> $thresholds
     * @return string
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
        return 'missingDocblock';
    }
}
