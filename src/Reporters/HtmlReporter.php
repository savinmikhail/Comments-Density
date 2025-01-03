<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Reporters;

use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\OutputDTO;

use function file_put_contents;
use function htmlspecialchars;
use function nl2br;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

final readonly class HtmlReporter implements ReporterInterface
{
    public function __construct(private string $reportPath) {}

    public function report(OutputDTO $dto): void
    {
        $html = "<html><head><meta charset='UTF-8'><title>Comment Density Report</title>";
        $html .= '<style>
            body { background-color: black; color: white; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid white; padding: 8px; }
            th { background-color: #333; }
            td { background-color: #444; }
            </style></head><body>';
        $html .= $this->generateHeader($dto);
        $html .= $this->generateCommentStatisticsTable($dto);
        $html .= $this->generateDetailedCommentsTable($dto);
        $html .= '</body></html>';

        file_put_contents($this->reportPath, $html);
    }

    private function generateHeader(OutputDTO $dto): string
    {
        return '<h1>Comment Density Report</h1>'
            . "<p><strong>Execution Time:</strong> {$dto->performanceDTO->executionTime} ms</p>"
            . "<p><strong>Peak Memory Usage:</strong> {$dto->performanceDTO->peakMemoryUsage} MB</p>"
            . "<p><strong>CDS:</strong> {$dto->cdsDTO->cds}</p>"
            . "<p><strong>Com/LoC:</strong> {$dto->comToLocDTO->comToLoc}</p>"
            . "<p><strong>Files analyzed:</strong> {$dto->filesAnalyzed}</p>";
    }

    private function generateCommentStatisticsTable(OutputDTO $dto): string
    {
        $html = '<h2>Comment Statistics</h2>';
        $html .= '<table><tr><th>Comment Type</th><th>Lines</th><th>Times</th></tr>';
        foreach ($dto->commentsStatistics as $commentStatisticsDTO) {
            $type = htmlspecialchars($commentStatisticsDTO->type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $count = htmlspecialchars((string) $commentStatisticsDTO->count, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $lines = htmlspecialchars((string) $commentStatisticsDTO->lines, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $color = htmlspecialchars($commentStatisticsDTO->typeColor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html .= "<tr>
                    <td style='color: {$color};'>{$type}</td>
                    <td style='color: white;'>{$lines}</td>
                    <td style='color: {$color};'>{$count}</td>
                </tr>";
        }
        $html .= '</table>';

        return $html;
    }

    private function generateDetailedCommentsTable(OutputDTO $dto): string
    {
        $html = '<h2>Detailed Comments</h2>';
        $html .= '<table>
            <tr>
                <th>Type</th>
                <th>File</th>
                <th>Line</th>
                <th>Content</th>
            </tr>';
        foreach ($dto->comments as $comment) {
            $commentType = htmlspecialchars($comment->commentType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $commentTypeColor = htmlspecialchars($comment->commentTypeColor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $file = htmlspecialchars($comment->file, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $line = htmlspecialchars((string) $comment->line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $content = nl2br(htmlspecialchars($comment->content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

            $html .= "<tr>
                <td style='color: {$commentTypeColor};'>{$commentType}</td>
                <td>{$file}</td>
                <td>{$line}</td>
                <td>{$content}</td>
            </tr>";
        }
        $html .= '</table>';

        return $html;
    }
}
