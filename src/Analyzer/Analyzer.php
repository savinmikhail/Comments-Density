<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Analyzer;

use Generator;
use SavinMikhail\CommentsDensity\Baseline\Storage\BaselineStorageInterface;
use SavinMikhail\CommentsDensity\Cache\Cache;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\DTO\Output\OutputDTO;
use SavinMikhail\CommentsDensity\Metrics\MetricsFacade;
use SavinMikhail\CommentsDensity\MissingDocblock\MissingDocBlockAnalyzer;
use Symfony\Component\Console\Output\OutputInterface;

use function array_push;

final class Analyzer
{
    private int $totalLinesOfCode = 0;

    public function __construct(
        private readonly ConfigDTO $configDTO,
        private readonly CommentFactory $commentFactory,
        private readonly MissingDocBlockAnalyzer $missingDocBlock,
        private readonly MetricsFacade $metrics,
        private readonly OutputInterface $output,
        private readonly MissingDocBlockAnalyzer $docBlockAnalyzer,
        private readonly BaselineStorageInterface $baselineStorage,
        private readonly Cache $cache,
        private readonly CommentStatisticsAggregator $statisticsAggregator,
    ) {}

    public function analyze(Generator $files): OutputDTO
    {
        $this->metrics->startPerformanceMonitoring();
        $comments = [];
        $filesAnalyzed = 0;

        foreach ($files as $file) {
            $task = new AnalyzeFileTask(
                $this->cache,
                $this->docBlockAnalyzer,
                $this->missingDocBlock,
                $this->commentFactory,
                $this->configDTO,
                $this->output,
            );

            $response = $task->run($file);

            $fileComments = $response['comments'];
            $lines = $response['lines'];

            array_push($comments, ...$fileComments);
            $this->totalLinesOfCode += $lines;
            ++$filesAnalyzed;
        }

        if ($this->configDTO->useBaseline) {
            $comments = $this->baselineStorage->filterComments($comments);
        }

        $commentStatistics = $this->statisticsAggregator->calculateCommentStatistics($comments);

        return $this->createOutputDTO($comments, $commentStatistics, $filesAnalyzed);
    }

    private function checkThresholdsExceeded(): bool
    {
        if ($this->metrics->hasExceededThreshold()) {
            return true;
        }
        if ($this->missingDocBlock->hasExceededThreshold()) {
            return true;
        }
        foreach ($this->commentFactory->getCommentTypes() as $commentType) {
            if ($commentType->hasExceededThreshold()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param CommentDTO[] $comments
     * @param CommentStatisticsDTO[] $preparedStatistics
     */
    private function createOutputDTO(
        array $comments,
        array $preparedStatistics,
        int $filesAnalyzed,
    ): OutputDTO {
        $comToLoc = $this->metrics->prepareComToLoc($preparedStatistics, $this->totalLinesOfCode);
        $cds = $this->metrics->prepareCDS($this->metrics->calculateCDS($preparedStatistics));
        $exceedThreshold = $this->checkThresholdsExceeded();
        $this->metrics->stopPerformanceMonitoring();
        $performanceMetrics = $this->metrics->getPerformanceMetrics();

        return new OutputDTO(
            $filesAnalyzed,
            $preparedStatistics,
            $comments,
            $performanceMetrics,
            $comToLoc,
            $cds,
            $exceedThreshold,
        );
    }
}
