<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

use function array_sum;

final readonly class CommentDensity
{
    public function __construct(
        private OutputInterface $output,
        private array $thresholds
    ) {
    }

    public function analyzeDirectory(string $directory): void
    {
        $files = glob("$directory/*.php");

        $totalLineCounts = [];
        $totalLinesOfCode = 0;

        foreach ($files as $filename) {
            $this->output->writeln("<info>Analyzing $filename</info>");
            $statistics = $this->getStatistics($filename);

            foreach ($statistics['lineCounts'] as $type => $count) {
                if (!isset($totalLineCounts[$type])) {
                    $totalLineCounts[$type] = 0;
                }
                $totalLineCounts[$type] += $count;
            }

            $totalLinesOfCode += $statistics['linesOfCode'];
        }

        $this->printStatistics($totalLineCounts, $totalLinesOfCode);
    }

    private function getStatistics(string $filename): array
    {
        $comments = $this->getCommentsFromFile($filename);
        $lineCounts = $this->countCommentLines($comments);
        $linesOfCode = $this->countTotalLines($filename);
        return [
            'lineCounts' => $lineCounts,
            'linesOfCode' => $linesOfCode,
        ];
    }

    public function printStatistics(array $lineCounts, int $linesOfCode): void
    {
        $table = new Table($this->output);
        $table
            ->setHeaders(['Comment Type', 'Lines'])
            ->setRows(array_map(function (string $type, int $count): array {
                $commentTypeColor = $this->getColorForCommentType(CommentType::tryFrom($type));
                $color = $this->getColorForThresholds(CommentType::tryFrom($type), $count);
                return ["<fg={$commentTypeColor}>{$type}</>", "<fg={$color}>{$count}</>"];
            }, array_keys($lineCounts), $lineCounts));

        $table->render();
        $totalComments = array_sum($lineCounts);
        $ratio = round($totalComments / $linesOfCode, 2);
        $color = $this->getColorForRatio($ratio);
        $this->output->writeln(["<fg=$color>Com/LoC: $ratio</>"]);
    }

    private function getColorForRatio(float $ratio): string
    {
        if (! isset($this->thresholds['Com/LoC'])) {
            return 'white';
        }
        return $ratio >= $this->thresholds['Com/LoC'] ? 'green' : 'red';
    }

    private function getColorForThresholds(CommentType $type, int $count): string
    {
        if (! isset($this->thresholds[$type->value])) {
            return 'white';
        }
        return match ($type->value) {
            'docBlock', 'license' => $count >= $this->thresholds[$type->value] ? 'green' : 'red',
            'regular', 'todo', 'fixme' => $count <= $this->thresholds[$type->value] ? 'green' : 'red',
        };
    }

    private function getColorForCommentType(CommentType $type): string
    {
        return match ($type->value) {
            'docBlock' => 'green',
            'regular' => 'red',
            'todo', 'fixme' => 'yellow',
            'license' => 'white',
        };
    }

    private function getCommentsFromFile(string $filename): array
    {
        $code = file_get_contents($filename);

        // Regex patterns for different types of comments
        $patterns = [
            'singleLine' => '/\/\/(?!.*\b(?:todo|fixme)\b:?).*/i', // Matches // comments, excludes TODO/FIXME, case-insensitive
            'multiLine'  => '/\/\*(?!\*|\s*\*.*\b(?:todo|fixme)\b:?).+?\*\//is', // Matches /* */ comments, excludes /** */ and those containing TODO/FIXME, case-insensitive
            'docBlock'   => '/\/\*\*(?!\s*\*\/)(?![\s\S]*?\b(license|copyright|permission)\b).+?\*\//is', // Matches docblocks, excludes licenses
            'todo'       => '/(?:\/\/|#|\/\*.*?\*\/).*\btodo\b:?.*/i', // Matches TODO comments, optional colon, case-insensitive
            'fixme'      => '/(?:\/\/|#|\/\*.*?\*\/).*\bfixme\b:?.*/i', // Matches FIXME comments, optional colon, case-insensitive
            'license'    => '/\/\*\*.*?\b(license|copyright|permission)\b.*?\*\//is' // Matches license information within docblocks
        ];

        $comments = [];

        // Apply regex patterns to find comments
        foreach ($patterns as $type => $pattern) {
            preg_match_all($pattern, $code, $matches);
            $comments[$type] = $matches[0];
        }
        $comments['regular'] = array_merge($comments['singleLine'], $comments['multiLine']);
        unset($comments['singleLine']);
        unset($comments['multiLine']);
        return $comments;
    }

    private function countCommentLines(array $comments): array
    {
        $lineCounts = [];
        foreach ($comments as $type => $commentArray) {
            $lineCounts[$type] = 0;
            foreach ($commentArray as $comment) {
                // Count the number of newlines in each comment and add 1 for the line itself
                $lineCounts[$type] += substr_count($comment, PHP_EOL) + 1;
            }
        }
        return $lineCounts;
    }

    private function countTotalLines(string $filename): int
    {
        $fileContent = file($filename);
        return count($fileContent);
    }
}
