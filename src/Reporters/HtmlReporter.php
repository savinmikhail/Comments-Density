<?php

namespace SavinMikhail\CommentsDensity\Reporters;

use SavinMikhail\CommentsDensity\DTO\Output\OutputDTO;

final readonly class HtmlReporter implements ReporterInterface
{
    public function __construct(private string $reportPath)
    {
    }

    public function report(OutputDTO $dto): void
    {
        $html = "<html><head><title>Comment Density Report</title></head><body>";
        $html .= "<h1>Comment Density Report</h1>";
        $html .= "<p><strong>Execution Time:</strong> {$dto->performanceMetricsDTO->executionTime} ms</p>";
        $html .= "<p><strong>Peak Memory Usage:</strong> {$dto->performanceMetricsDTO->peakMemoryUsage} MB</p>";
        $html .= "<p><strong>CDS:</strong> {$dto->cdsDTO->cds}</p>";
        $html .= "<p><strong>Com/LoC:</strong> {$dto->comToLocDTO->comToLoc}</p>";
        $html .= "<p><strong>Files analyzed:</strong> {$dto->filesAnalyzed}</p>";

        $html .= "<h2>Comment Statistics</h2>";
        $html .= "<table border='1'><tr><th>Comment Type</th><th>Lines</th></tr>";
        foreach ($dto->commentsStatistics as $commentStatisticsDTO) {
            $html .= "<tr><td style='color: {$commentStatisticsDTO->typeColor};'>{$commentStatisticsDTO->type}</td><td style='color: {$commentStatisticsDTO->typeColor};'>{$commentStatisticsDTO->count}</td></tr>";
        }
        $html .= "</table>";

        $html .= "<h2>Detailed Comments</h2>";
        $html .= "<table border='1'><tr><th>Type</th><th>File</th><th>Line</th><th>Content</th></tr>";
        foreach ($dto->comments as $comment) {
            $commentType = htmlspecialchars($comment->commentType);
            $commentTypeColor = htmlspecialchars($comment->commentTypeColor);
            $file = htmlspecialchars($comment->file);
            $line = htmlspecialchars((string)$comment->line);
            $content = htmlspecialchars($comment->content);

            $html .= "<tr>
                <td style='color: $commentTypeColor;'>$commentType}</td>
                <td>$file</td>
                <td>$line</td>
                <td>$content</td>
            </tr>";
        }
        $html .= "</table>";

        $html .= "</body></html>";

        file_put_contents($this->reportPath, $html);
    }
}
