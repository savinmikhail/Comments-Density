<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Analyzer;

use SavinMikhail\CommentsDensity\Baseline\BaselineManager;
use SavinMikhail\CommentsDensity\Baseline\Storage\BaselineStorageInterface;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\Metrics\CDS;
use SavinMikhail\CommentsDensity\Metrics\ComToLoc;
use SavinMikhail\CommentsDensity\Metrics\MetricsFacade;
use SavinMikhail\CommentsDensity\Metrics\PerformanceMonitor;
use SavinMikhail\CommentsDensity\MissingDocblock\MissingDocBlockAnalyzer;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class AnalyzerFactory
{
    public function getAnalyzer(
        ConfigDTO $configDto,
        OutputInterface $output,
        BaselineStorageInterface $baselineStorage,
    ): Analyzer {
        $commentFactory = new CommentFactory($configDto->only);
        $missingDocBlock = new MissingDocBlockAnalyzer($configDto->docblockConfigDTO);
        $cds = new CDS($configDto->thresholds, $commentFactory);

        $metrics = new MetricsFacade(
            $cds,
            new ComToLoc($configDto->thresholds),
            new PerformanceMonitor()
        );

        return new Analyzer(
            $configDto,
            $commentFactory,
            $missingDocBlock,
            $metrics,
            $output,
            $missingDocBlock,
            $baselineStorage,
        );
    }
}