<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\Comments\CommentTypeInterface;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\DTO\Output\OutputDTO;
use SavinMikhail\CommentsDensity\Metrics\Metrics;
use SavinMikhail\CommentsDensity\Reporters\ReporterInterface;
use SplFileInfo;

use function array_keys;
use function in_array;

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

    public function analyzeFiles(array $files): bool
    {
        return $this->analyze($files);
    }

    private function analyze(array $files): bool
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

        $this->removeUnusedStatistics($comments, $commentStatistics);

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

    protected function removeUnusedStatistics(array &$comments, array &$commentStatistics): void
    {
        if (! $this->configDTO->only) {
            return;
        }
        foreach ($comments as $key => $comment) {
            if (! in_array((string) $comment['type'], $this->configDTO->only)) {
                unset($comments[$key]);
            }
        }
        foreach (array_keys($commentStatistics) as $commentType) {
            if (! in_array($commentType, $this->configDTO->only)) {
                unset($commentStatistics[$commentType]);
            }
        }
    }

    private function createOutputDTO(
        array $comments,
        array $commentStatistics,
        int $linesOfCode,
        float $cds,
        int $filesAnalyzed,
    ): OutputDTO {
        $outputDTO = new OutputDTO(
            $filesAnalyzed,
            $this->prepareCommentStatistics($commentStatistics),
            $this->prepareComments($comments),
            $this->metrics->getPerformanceMetrics(),
            $this->metrics->prepareComToLoc($commentStatistics, $linesOfCode),
            $this->metrics->prepareCDS($cds)
        );
        if ($this->metrics->hasExceededThreshold()) {
            $this->exceedThreshold = true;
        }
        return $outputDTO;
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
                    $count,
                    $commentType->getStatColor($count, $this->configDTO->thresholds)
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
