<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer;

use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\CommentTypeFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;
use SavinMikhail\CommentsDensity\AnalyzeComments\Metrics\CDS;
use SavinMikhail\CommentsDensity\AnalyzeComments\Metrics\ComToLoc;
use SavinMikhail\CommentsDensity\AnalyzeComments\Metrics\MetricsFacade;
use SavinMikhail\CommentsDensity\AnalyzeComments\Metrics\ResourceUtilization;
use SavinMikhail\CommentsDensity\Baseline\Storage\BaselineStorageInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final readonly class AnalyzerFactory
{
    public function getAnalyzer(
        Config                   $configDto,
        BaselineStorageInterface $baselineStorage,
    ): Analyzer {
        $commentFactory = new CommentTypeFactory($configDto->getAllowedTypes());
        $cds = new CDS($configDto->thresholds, $commentFactory);

        $metrics = new MetricsFacade(
            $cds,
            new ComToLoc($configDto->thresholds),
            new ResourceUtilization(),
        );

        return new Analyzer(
            $configDto,
            $commentFactory,
            $metrics,
            $baselineStorage,
            new FilesystemAdapter(directory: $configDto->cacheDir),
            new CommentStatisticsAggregator($configDto, $commentFactory),
        );
    }
}
