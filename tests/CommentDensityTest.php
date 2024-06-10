<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity;

use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\CDS;
use SavinMikhail\CommentsDensity\CommentDensity;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\ComToLoc;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\FileAnalyzer;
use SavinMikhail\CommentsDensity\MissingDocBlockAnalyzer;
use SavinMikhail\CommentsDensity\Reporters\ConsoleReporter;
use SavinMikhail\CommentsDensity\StatisticCalculator;
use Symfony\Component\Console\Output\BufferedOutput;

final class CommentDensityTest extends TestCase
{
    public function testAnalyzeDirectories(): void
    {
        $config = new ConfigDTO(
            ['CDS' => 0.5, 'Com/LoC' => 0.1],
            [],
            []
        );
        $output = new BufferedOutput();
        $commentFactory = new CommentFactory();
        $missingDocBlockAnalyzer = new MissingDocBlockAnalyzer();
        $cds = new CDS($config->thresholds, $commentFactory);
        $fileAnalyzer = new FileAnalyzer($output, $missingDocBlockAnalyzer, $cds, $commentFactory);
        $reporter = new ConsoleReporter($output);

        $analyzer = new CommentDensity(
            $config,
            $commentFactory,
            $fileAnalyzer,
            $reporter,
            $cds,
            new ComToLoc($config->thresholds),
            $missingDocBlockAnalyzer,
        );

        $directories = [__DIR__ . '/TestFiles'];
        $result = $analyzer->analyzeDirectories($directories);
        // thresholds exceeded
        $this->assertTrue($result);
        $this->assertStringContainsString('Files analyzed: ', $output->fetch());
    }
}
