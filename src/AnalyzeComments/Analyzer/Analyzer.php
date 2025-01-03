<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer;

use Psr\Cache\InvalidArgumentException;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\Report;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\CommentTypeFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConfigDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Exception\CommentsDensityException;
use SavinMikhail\CommentsDensity\AnalyzeComments\Metrics\MetricsFacade;
use SavinMikhail\CommentsDensity\Baseline\Storage\BaselineStorageInterface;
use SplFileInfo;
use Symfony\Contracts\Cache\CacheInterface;

use function array_push;

final class Analyzer
{
    private int $totalLinesOfCode = 0;

    public function __construct(
        private readonly ConfigDTO $configDTO,
        private readonly CommentTypeFactory $commentFactory,
        private readonly MetricsFacade $metrics,
        private readonly BaselineStorageInterface $baselineStorage,
        private readonly CacheInterface $cache,
        private readonly CommentStatisticsAggregator $statisticsAggregator,
    ) {}

    /**
     * @param SplFileInfo[] $files
     * @throws CommentsDensityException|InvalidArgumentException
     */
    public function analyze(iterable $files): Report
    {
        $this->metrics->startPerformanceMonitoring();
        $comments = [];
        $filesAnalyzed = 0;

        foreach ($files as $file) {
            $contentExtractor = new FileContentExtractor($file, $this->configDTO);
            if ($contentExtractor->shouldSkipFile()) {
                continue;
            }
            $task = new CommentFinder(
                $this->commentFactory,
                $this->configDTO,
            );

            $fileComments = $this->cache->get(
                $this->getCacheKey($file),
                static fn(): array => $task->run($contentExtractor->getContent(), $file->getRealPath()),
            );

            $lines = (new FileTotalLinesCounter())->run($file);

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

    private function getCacheKey(SplFileInfo $file): string
    {
        $filePath = $file->getRealPath();
        $lastModified = filemtime($filePath);

        return md5($filePath . $lastModified);
    }

    private function checkThresholdsExceeded(): bool
    {
        if ($this->metrics->hasExceededThreshold()) {
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
    ): Report {
        $comToLoc = $this->metrics->prepareComToLoc($preparedStatistics, $this->totalLinesOfCode);
        $cds = $this->metrics->prepareCDS($this->metrics->calculateCDS($preparedStatistics));
        $exceedThreshold = $this->checkThresholdsExceeded();
        $this->metrics->stopPerformanceMonitoring();
        $performanceMetrics = $this->metrics->getPerformanceMetrics();

        return new Report(
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
