<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\Comments\CommentTypeInterface;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CdsDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\DTO\Output\ComToLocDTO;
use SavinMikhail\CommentsDensity\DTO\Output\OutputDTO;
use SavinMikhail\CommentsDensity\DTO\Output\PerformanceMetricsDTO;
use SavinMikhail\CommentsDensity\Reporters\ReporterInterface;
use SplFileInfo;

use function round;

final class CommentDensity
{
    private bool $exceedThreshold = false;

    public function __construct(
        private readonly ConfigDTO $configDTO,
        private readonly CommentFactory $commentFactory,
        private readonly FileAnalyzer $fileAnalyzer,
        private readonly ReporterInterface $reporter,
    ) {
    }

    public function analyzeDirectories(array $directories): bool
    {
        $startTime = microtime(true);
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

        $endTime = microtime(true);
        $executionTimeMS = round(($endTime - $startTime) * 1000, 2);
        $peakMemoryUsage = memory_get_peak_usage(true);

        $outputDTO = $this->createOutputDTO(
            $comments,
            $commentStatistics,
            $totalLinesOfCode,
            $cdsSum / $totalLinesOfCode,
            $filesAnalyzed,
            $executionTimeMS,
            $peakMemoryUsage
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
        float $executionTime,
        float $peakMemoryUsage
    ): OutputDTO {
        $performanceMetricsDTO = new PerformanceMetricsDTO(
            $executionTime,
            round($peakMemoryUsage / 1024 / 1024, 2)
        );
        return new OutputDTO(
            $filesAnalyzed,
            $this->prepareCommentStatistics($commentStatistics),
            $this->prepareComments($comments),
            $performanceMetricsDTO,
            $this->prepareComToLoc($commentStatistics, $linesOfCode),
            $this->prepareCDS($cds)
        );
    }

    private function prepareCDS(float $cds): CdsDTO
    {
        $cds = round($cds, 2);
        return new CdsDTO(
            $cds,
            $this->getColorForCDS($cds),
        );
    }

    private function getColorForCDS(float $cds): string
    {
        if (! isset($this->thresholds['CDS'])) {
            return 'white';
        }
        if ($cds >= $this->thresholds['CDS']) {
            return 'green';
        }
        $this->exceedThreshold = true;
        return 'red';
    }

    private function prepareComToLoc(array $commentStatistics, int $linesOfCode): ComToLocDTO
    {
        $ratio = $this->getRatio($commentStatistics, $linesOfCode);
        return new ComToLocDTO(
            $ratio,
            $this->getColorForRatio($ratio)
        );
    }

    private function getRatio(array $commentStatistics, int $linesOfCode): float
    {
        $totalComments = array_sum($commentStatistics);
        return round($totalComments / $linesOfCode, 2);
    }

    private function getColorForRatio(float $ratio): string
    {
        if (! isset($this->thresholds['Com/LoC'])) {
            return 'white';
        }
        if ($ratio >= $this->thresholds['Com/LoC']) {
            return 'green';
        }
        $this->exceedThreshold = true;
        return 'red';
    }

    private function prepareCommentStatistics(array $commentStatistics): array
    {
        $preparedCommentStatistics = [];
        foreach ($commentStatistics as $type => $count) {
            if ($type === 'missingDocblock') {
                $preparedCommentStatistics[] = new CommentStatisticsDTO(
                    $this->getMissingDocBlockColor(),
                    'missingDocblock',
                    $count,
                    $this->getMissingDocBlockStatColor($count)
                );
                continue;
            }
            $commentType = $this->commentFactory->getCommentType($type);
            if ($commentType) {
                $preparedCommentStatistics[] = new CommentStatisticsDTO(
                    $commentType->getColor(),
                    $commentType->getName(),
                    $count,
                    $commentType->getStatColor($count, $this->configDTO->thresholds)
                );
            }
        }
        return  $preparedCommentStatistics;
    }

    private function getMissingDocBlockColor(): string
    {
        return 'red';
    }

    private function getMissingDocBlockStatColor(float $count): string
    {
        if (! isset($this->thresholds['missingDocBlock'])) {
            return 'white';
        }
        if ($count <= $this->thresholds['missingDocBlock']) {
            return 'green';
        }
        $this->exceedThreshold = true;
        return 'red';
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
