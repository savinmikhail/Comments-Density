<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use Generator;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\Comments\CommentTypeInterface;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\DTO\Output\OutputDTO;
use SavinMikhail\CommentsDensity\Metrics\MetricsFacade;
use SavinMikhail\CommentsDensity\MissingDocblock\MissingDocBlockAnalyzer;
use SavinMikhail\CommentsDensity\Reporters\ReporterInterface;
use SplFileInfo;

use function str_contains;

final class CommentDensity
{
    private bool $exceedThreshold = false;

    public function __construct(
        private readonly ConfigDTO $configDTO,
        private readonly CommentFactory $commentFactory,
        private readonly FileAnalyzer $fileAnalyzer,
        private readonly MissingDocBlockAnalyzer $missingDocBlock,
        private readonly MetricsFacade $metrics,
    ) {
    }

    public function analyze(Generator $files): OutputDTO
    {
        $this->metrics->startPerformanceMonitoring();
        $comments = [];
        $commentStatistics = [];
        $totalLinesOfCode = 0;
        $cdsSum = 0;
        $filesAnalyzed = 0;

        foreach ($files as $file) {
            if (! ($file instanceof SplFileInfo)) {
                continue;
            }
            if ($this->isInWhitelist($file->getRealPath())) {
                continue;
            }
            if ($file->getSize() === 0) {
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

        $this->metrics->stopPerformanceMonitoring();

        return $this->createOutputDTO(
            $comments,
            $commentStatistics,
            $totalLinesOfCode,
            $this->calcAvgCDS($totalLinesOfCode, $cdsSum),
            $filesAnalyzed,
        );
    }

    private function calcAvgCDS(int $totalLinesOfCode, float $cdsSum): float
    {
        return $totalLinesOfCode === 0 ? 0 : $cdsSum / $totalLinesOfCode;
    }

    private function createOutputDTO(
        array $comments,
        array $commentStatistics,
        int $linesOfCode,
        float $cds,
        int $filesAnalyzed,
    ): OutputDTO {
        $preparedCommentStatistic = $this->prepareCommentStatistics($commentStatistics);
        $preparedComments = $this->prepareComments($comments);
        $performanceMetrics = $this->metrics->getPerformanceMetrics();
        $comToLoc = $this->metrics->prepareComToLoc($commentStatistics, $linesOfCode);
        $cds = $this->metrics->prepareCDS($cds);
        if ($this->metrics->hasExceededThreshold()) {
            $this->exceedThreshold = true;
        }
        return new OutputDTO(
            $filesAnalyzed,
            $preparedCommentStatistic,
            $preparedComments,
            $performanceMetrics,
            $comToLoc,
            $cds,
            $this->exceedThreshold
        );
    }

    private function prepareCommentStatistics(array $commentStatistics): array
    {
        $preparedStatistics = [];
        foreach ($commentStatistics as $type => $stat) {
            if ($type === 'missingDocblock') {
                $preparedStatistics[] = new CommentStatisticsDTO(
                    $this->missingDocBlock->getColor(),
                    $this->missingDocBlock->getName(),
                    $stat['lines'],
                    $this->missingDocBlock->getStatColor($stat['count'], $this->configDTO->thresholds),
                    $stat['count']
                );
                if ($this->missingDocBlock->hasExceededThreshold()) {
                    $this->exceedThreshold = true;
                }
                continue;
            }
            $commentType = $this->commentFactory->getCommentType($type);
            if ($commentType) {
                $preparedStatistics[] = new CommentStatisticsDTO(
                    $commentType->getColor(),
                    $commentType->getName(),
                    $stat['lines'],
                    $commentType->getStatColor($stat['count'], $this->configDTO->thresholds),
                    $stat['count']
                );
                if ($commentType->hasExceededThreshold()) {
                    $this->exceedThreshold = true;
                }
            }
        }
        return $preparedStatistics;
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
            if ($commentType->getWeight() > 0) {
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
