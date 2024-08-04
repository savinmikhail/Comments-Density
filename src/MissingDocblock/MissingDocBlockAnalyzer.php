<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use SavinMikhail\CommentsDensity\DTO\Input\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\MissingDocblock\Visitors\Checkers\MethodNeedsDocblockChecker;
use SavinMikhail\CommentsDensity\MissingDocblock\Visitors\Checkers\NodeNeedsDocblockChecker;
use SavinMikhail\CommentsDensity\MissingDocblock\Visitors\MissingDocBlockVisitor;

final class MissingDocBlockAnalyzer
{
    /**
     * @readonly
     */
    private MissingDocblockConfigDTO $docblockConfigDTO;
    public const NAME = 'missingDocblock';
    public const COLOR = 'red';

    private bool $exceedThreshold = false;

    public function __construct(MissingDocblockConfigDTO $docblockConfigDTO)
    {
        $this->docblockConfigDTO = $docblockConfigDTO;
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

        $nameResolver = new NameResolver();
        $traverser->addVisitor($nameResolver);
        $fqnNodes = $traverser->traverse($ast);

        $visitor = new MissingDocBlockVisitor(
            $filename,
            new NodeNeedsDocblockChecker($this->docblockConfigDTO, new MethodNeedsDocblockChecker())
        );
        $traverser->removeVisitor($nameResolver);
        $traverser->addVisitor($visitor);
        $traverser->traverse($fqnNodes);

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
        return self::COLOR;
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
        return self::NAME;
    }
}
