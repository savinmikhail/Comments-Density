<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer;

use Psr\Cache\InvalidArgumentException;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\Report;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\CommentTypeFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;
use SavinMikhail\CommentsDensity\AnalyzeComments\Exception\CommentsDensityException;
use SavinMikhail\CommentsDensity\AnalyzeComments\File\CommentFinder;
use SavinMikhail\CommentsDensity\AnalyzeComments\File\FileContentExtractor;
use SavinMikhail\CommentsDensity\AnalyzeComments\File\FileFinder;
use SavinMikhail\CommentsDensity\AnalyzeComments\File\FileTotalLinesCounter;
use SavinMikhail\CommentsDensity\AnalyzeComments\Metrics\MetricsFacade;
use SavinMikhail\CommentsDensity\Baseline\Storage\BaselineStorageInterface;
use SplFileInfo;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class Analyzer
{
    public function __construct(
        private Config $configDTO,
        private CommentTypeFactory $commentFactory,
        private MetricsFacade $metrics,
        private BaselineStorageInterface $baselineStorage,
        private CacheInterface $cache,
        private CommentStatisticsAggregator $statisticsAggregator,
    ) {}

    /**
     * @throws CommentsDensityException
     * @throws InvalidArgumentException
     */
    public function analyze(): Report
    {
        $this->metrics->startPerformanceMonitoring();
        $comments = [];
        $filesAnalyzed = 0;
        $totalLinesOfCode = 0;
        foreach ((new FileFinder($this->configDTO))() as $file) {
            $commentFinder = new CommentFinder(
                $this->commentFactory,
                $this->configDTO,
            );
            $contentExtractor = new FileContentExtractor($file, $this->configDTO);

            $fileComments = $this->cache->get(
                $this->getCacheKey($file),
                static fn(): array => $commentFinder($contentExtractor->getContent(), $file->getRealPath()),
            );

            $lines = (new FileTotalLinesCounter($file))();

            $comments = [...$comments, ...$fileComments];
            $totalLinesOfCode += $lines;
            ++$filesAnalyzed;
        }

        if ($this->configDTO->useBaseline) {
            $comments = $this->baselineStorage->filterComments($comments);
        }

        $commentStatistics = $this->statisticsAggregator->calculateCommentStatistics($comments);

        return $this->createReport($comments, $commentStatistics, $filesAnalyzed, $totalLinesOfCode);
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
    private function createReport(
        array $comments,
        array $preparedStatistics,
        int $filesAnalyzed,
        int $totalLinesOfCode,
    ): Report {
        $comToLoc = $this->metrics->prepareComToLoc($preparedStatistics, $totalLinesOfCode);
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
