<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Reporters;

use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\DTO\Output\OutputDTO;
use SavinMikhail\CommentsDensity\DTO\Output\PerformanceMetricsDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CdsDTO;
use SavinMikhail\CommentsDensity\DTO\Output\ComToLocDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentStatisticsDTO;
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
        $outputDTO = new OutputDTO(2, $commentsStatistics, $comments, $performanceDTO, $comToLocDTO, $cdsDTO);

        $reporter->report($outputDTO);

        $this->assertFileExists($this->reportPath);

        $htmlContent = file_get_contents($this->reportPath);
        $this->assertStringContainsString('<title>Comment Density Report</title>', $htmlContent);
        $this->assertStringContainsString('<h1>Comment Density Report</h1>', $htmlContent);
        $this->assertStringContainsString('<p><strong>Execution Time:</strong> 123 ms</p>', $htmlContent);
        $this->assertStringContainsString('<p><strong>CDS:</strong> 0.45</p>', $htmlContent);
        $this->assertStringContainsString('<td style=\'color: #000000;\'>docblock</td>', $htmlContent);
        $this->assertStringContainsString('<td>This is a docblock comment</td>', $htmlContent);
    }
}
