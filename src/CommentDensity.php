<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\Comments\CommentTypeInterface;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\DTO\Output\OutputDTO;
use SavinMikhail\CommentsDensity\Metrics\Metrics;
use SavinMikhail\CommentsDensity\Reporters\ReporterInterface;
use SplFileInfo;

final class CommentDensity
{
    private bool $exceedThreshold = false;

    public function __construct(
        private readonly ConfigDTO $configDTO,
        private readonly CommentFactory $commentFactory,
        private readonly FileAnalyzer $fileAnalyzer,
        private readonly ReporterInterface $reporter,
        private readonly MissingDocBlockAnalyzer $missingDocBlock,
        private readonly Metrics $metrics,
    ) {
    }

    public function analyzeDirectories(array $directories): bool
    {
        $this->metrics->startPerformanceMonitoring();
        $comments = [];
        $commentStatistics = [];
        $totalLinesOfCode = 0;
        $cdsSum = 0;
        $filesAnalyzed = 0;

        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($this->isInWhitelist($file->getRealPath())) {
                    continue;
                }
                $this->fileAnalyzer->analyzeFile(
                    $file,
                    $commentStatistics,
                    $comments,
                    $totalLinesOfCode,
                    $cdsSum
                );
                $filesAnalyzed++;
            }
        }

        $this->metrics->stopPerformanceMonitoring();

        $outputDTO = $this->createOutputDTO(
            $comments,
            $commentStatistics,
            $totalLinesOfCode,
            $cdsSum / $totalLinesOfCode,
            $filesAnalyzed,
        );

        $this->reporter->report($outputDTO);

        return $this->exceedThreshold;
    }

    private function createOutputDTO(
        array $comments,
        array $commentStatistics,
        int $linesOfCode,
        float $cds,
        int $filesAnalyzed,
    ): OutputDTO {
        if ($this->metrics->hasExceededThreshold()) {
            $this->exceedThreshold = true;
        }
        return new OutputDTO(
            $filesAnalyzed,
            $this->prepareCommentStatistics($commentStatistics),
            $this->prepareComments($comments),
            $this->metrics->getPerformanceMetrics(),
            $this->metrics->prepareComToLoc($commentStatistics, $linesOfCode),
            $this->metrics->prepareCDS($cds)
        );
    }

    private function prepareCommentStatistics(array $commentStatistics): array
    {
        $preparedStatistics = [];
        foreach ($commentStatistics as $type => $count) {
            if ($type === 'missingDocblock') {
                $preparedStatistics[] = new CommentStatisticsDTO(
                    $this->missingDocBlock->getColor(),
                    $this->missingDocBlock->getName(),
                    $count,
                    $this->missingDocBlock->getStatColor($count, $this->configDTO->thresholds)
                );
                $this->exceedThreshold = $this->exceedThreshold ?: $this->missingDocBlock->hasExceededThreshold();
                continue;
            }
            $commentType = $this->commentFactory->getCommentType($type);
            if ($commentType) {
                $preparedStatistics[] = new CommentStatisticsDTO(
                    $commentType->getColor(),
                    $commentType->getName(),
                    $count,
                    $commentType->getStatColor($count, $this->configDTO->thresholds)
                );
                $this->exceedThreshold = $this->exceedThreshold ?: $commentType->hasExceededThreshold();
            }
        }
        return  $preparedStatistics;
    }

    private function prepareComments(array $comments): array
    {
        $preparedComments = [];
        foreach ($comments as $comment) {
            /** @var CommentTypeInterface|string $commentType */
            $commentType = $comment['type'];
            if ($commentType === 'missingDocblock') {
                $preparedComments[] = new CommentDTO(
                    'missingDocblock',
                    'red',
                    $comment['file'],
                    $comment['line'],
                    $comment['content']
                );
                continue;
            }
            if ($commentType->getAttitude() === 'good') {
                continue;
            }
            $preparedComments[] = new CommentDTO(
                $commentType->getName(),
                $commentType->getColor(),
                $comment['file'],
                $comment['line'],
                $comment['content']
            );
        }
        return $preparedComments;
    }

    public function analyzeFile(string $filename): bool
    {
        $comments = [];
        $commentStatistics = [];
        $totalLinesOfCode = 0;
        $cdsSum = 0;

        $this->fileAnalyzer->analyzeFile(
            new SplFileInfo($filename),
            $commentStatistics,
            $comments,
            $totalLinesOfCode,
            $cdsSum
        );

        return $this->exceedThreshold;
    }

    private function isInWhitelist(string $filePath): bool
    {
        foreach ($this->configDTO->exclude as $whitelistedDir) {
            if (str_contains($filePath, $whitelistedDir)) {
                return true;
            }
        }
        return false;
    }
}
