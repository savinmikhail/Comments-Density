<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer;

use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConfigDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Metrics\CDS;
use SavinMikhail\CommentsDensity\AnalyzeComments\Metrics\ComToLoc;
use SavinMikhail\CommentsDensity\AnalyzeComments\Metrics\MetricsFacade;
use SavinMikhail\CommentsDensity\AnalyzeComments\Metrics\ResourceUtilization;
use SavinMikhail\CommentsDensity\AnalyzeComments\MissingDocblock\MissingDocBlockAnalyzer;
use SavinMikhail\CommentsDensity\Baseline\Storage\BaselineStorageInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class AnalyzerFactory
{
    public function getAnalyzer(
        ConfigDTO $configDto,
        OutputInterface $output,
        BaselineStorageInterface $baselineStorage,
    ): Analyzer {
        $commentFactory = new CommentFactory($configDto->getAllowedTypes());
        $missingDocBlock = new MissingDocBlockAnalyzer($configDto->docblockConfigDTO);
        $cds = new CDS($configDto->thresholds, $commentFactory);

        $metrics = new MetricsFacade(
            $cds,
            new ComToLoc($configDto->thresholds),
            new ResourceUtilization(),
        );

        return new Analyzer(
            $configDto,
            $commentFactory,
            $missingDocBlock,
            $metrics,
            $output,
            $missingDocBlock,
            $baselineStorage,
            new FilesystemAdapter(directory: $configDto->cacheDir),
            new CommentStatisticsAggregator($configDto, $commentFactory, $missingDocBlock),
        );
    }
}