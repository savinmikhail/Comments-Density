<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Reporters;

use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\CdsDTO;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\ComToLocDTO;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\OutputDTO;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\PerformanceMetricsDTO;
use SavinMikhail\CommentsDensity\Reporters\HtmlReporter;

final class HtmlReporterTest extends TestCase
{
    private string $reportPath;

    protected function setUp(): void
    {
        $this->reportPath = __DIR__ . '/../../../report.html';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->reportPath)) {
            unlink($this->reportPath);
        }
    }

    public function testReportGeneration(): void
    {
        $reporter = new HtmlReporter($this->reportPath);

        $performanceDTO = new PerformanceMetricsDTO(123, 1.23);
        $cdsDTO = new CdsDTO(0.45, 'red');
        $comToLocDTO = new ComToLocDTO(0.12, 'red');
        $commentsStatistics = [
            new CommentStatisticsDTO('#000000', 'docblock', 10, '#000000', 2),
            new CommentStatisticsDTO('#FF0000', 'regular', 5, '#FF0000', 3),
        ];
        $comments = [
            new CommentDTO('docblock', '#000000', 'some/file.php', 10, 'This is a docblock comment'),
            new CommentDTO('inline', '#FF0000', 'another/file.php', 20, 'This is an inline comment'),
        ];
        $outputDTO = new OutputDTO(2, $commentsStatistics, $comments, $performanceDTO, $comToLocDTO, $cdsDTO, false);

        $reporter->report($outputDTO);

        self::assertFileExists($this->reportPath);

        $htmlContent = file_get_contents($this->reportPath);
        self::assertStringContainsString('<title>Comment Density Report</title>', $htmlContent);
        self::assertStringContainsString('<h1>Comment Density Report</h1>', $htmlContent);
        self::assertStringContainsString('<p><strong>Execution Time:</strong> 123 ms</p>', $htmlContent);
        self::assertStringContainsString('<p><strong>CDS:</strong> 0.45</p>', $htmlContent);
        self::assertStringContainsString('<td style=\'color: #000000;\'>docblock</td>', $htmlContent);
        self::assertStringContainsString('<td>This is a docblock comment</td>', $htmlContent);
    }
}
