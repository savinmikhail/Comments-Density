<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\Metrics\CDS;
use SavinMikhail\CommentsDensity\Metrics\ComToLoc;
use SavinMikhail\CommentsDensity\Metrics\Metrics;
use SavinMikhail\CommentsDensity\Metrics\PerformanceMonitor;
use SavinMikhail\CommentsDensity\Reporters\ReporterInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AnalyzerFactory
{
    public function getAnalyzer(
        ConfigDTO $configDto,
        OutputInterface $output,
        ReporterInterface $reporter
    ): CommentDensity {
        $commentFactory = new CommentFactory($configDto->only);
        $missingDocBlock = new MissingDocBlockAnalyzer();
        $cds = new CDS($configDto->thresholds, $commentFactory);

        $fileAnalyzer = new FileAnalyzer(
            $output,
            $missingDocBlock,
            $cds,
            $commentFactory,
            $configDto,
        );

        $metrics = new Metrics(
            $cds,
            new ComToLoc($configDto->thresholds),
            new PerformanceMonitor()
        );

        return new CommentDensity(
            $configDto,
            $commentFactory,
            $fileAnalyzer,
            $reporter,
            $missingDocBlock,
            $metrics
        );
    }
}
