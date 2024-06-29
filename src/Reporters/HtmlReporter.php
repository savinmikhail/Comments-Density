<?php

namespace SavinMikhail\CommentsDensity\Reporters;

use SavinMikhail\CommentsDensity\DTO\Output\OutputDTO;

use function file_put_contents;
use function htmlspecialchars;

final class HtmlReporter implements ReporterInterface
{
    /**
     * @readonly
     */
    private string $reportPath;
    public function __construct(string $reportPath)
    {
        $this->reportPath = $reportPath;
    }
    public function report(OutputDTO $dto): void
    {
        $html = "<html><head><title>Comment Density Report</title></head><body>";
        $html .= $this->generateHeader($dto);
        $html .= $this->generateCommentStatisticsTable($dto);
        $html .= $this->generateDetailedCommentsTable($dto);
        $html .= "</body></html>";

        file_put_contents($this->reportPath, $html);
    }

    private function generateHeader(OutputDTO $dto): string
    {
        return "<h1>Comment Density Report</h1>"
            . "<p><strong>Execution Time:</strong> {$dto->performanceDTO->executionTime} ms</p>"
            . "<p><strong>Peak Memory Usage:</strong> {$dto->performanceDTO->peakMemoryUsage} MB</p>"
            . "<p><strong>CDS:</strong> {$dto->cdsDTO->cds}</p>"
            . "<p><strong>Com/LoC:</strong> {$dto->comToLocDTO->comToLoc}</p>"
            . "<p><strong>Files analyzed:</strong> {$dto->filesAnalyzed}</p>";
    }

    private function generateCommentStatisticsTable(OutputDTO $dto): string
    {
        $html = "<h2>Comment Statistics</h2>";
        $html .= "<table border='1'><tr><th>Comment Type</th><th>Lines</th></tr>";
        foreach ($dto->commentsStatistics as $commentStatisticsDTO) {
            $html .= "<tr>
                    <td style='color: {$commentStatisticsDTO->typeColor};'>{$commentStatisticsDTO->type}</td>
                    <td style='color: {$commentStatisticsDTO->typeColor};'>{$commentStatisticsDTO->count}</td>
                </tr>";
        }
        $html .= "</table>";

        return $html;
    }

    private function generateDetailedCommentsTable(OutputDTO $dto): string
    {
        $html = "<h2>Detailed Comments</h2>";
        $html .= "<table border='1'>
            <tr>
                <th>Type</th>
                <th>File</th>
                <th>Line</th>
                <th>Content</th>
            </tr>";
        foreach ($dto->comments as $comment) {
            $commentType = htmlspecialchars($comment->commentType);
            $commentTypeColor = htmlspecialchars($comment->commentTypeColor);
            $file = htmlspecialchars($comment->file);
            $line = htmlspecialchars((string)$comment->line);
            $content = htmlspecialchars($comment->content);

            $html .= "<tr>
                <td style='color: $commentTypeColor;'>$commentType</td>
                <td>$file</td>
                <td>$line</td>
                <td>$content</td>
            </tr>";
        }
        $html .= "</table>";

        return $html;
    }
}
