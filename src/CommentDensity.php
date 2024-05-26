<?php

declare(strict_types=1);

namespace Savinmikhail\CommentsDensity;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

use function print_r;

final readonly class CommentDensity
{
    public function __construct(private string $filename, private OutputInterface $output)
    {
    }

    public function printStatistics(): void
    {
        $comments = $this->getCommentsFromFile();
        $lineCounts = $this->countCommentLines($comments);
        $totalLines = $this->countTotalLines($this->filename);

        $table = new Table($this->output);
        $table
            ->setHeaders(['Comment Type', 'Lines'])
            ->setRows(array_map(function ($type, $count) {
                return [$type, $count];
            }, array_keys($lineCounts), $lineCounts));

        $table->render();

        $this->output->writeln("<info>Total lines in file: $totalLines</info>");
    }

    private function getCommentsFromFile(): array
    {
        $code = file_get_contents($this->filename);

        // Regex patterns for different types of comments
        $patterns = [
            'singleLine' => '/\/\/(?!.*\b(?:todo|fixme)\b:?).*/i', // Matches // comments, excludes TODO/FIXME, case-insensitive
            'multiLine'  => '/\/\*(?!\*|\s*\*.*\b(?:todo|fixme)\b:?).+?\*\//is', // Matches /* */ comments, excludes /** */ and those containing TODO/FIXME, case-insensitive
            'docBlock'   => '/\/\*\*(?!\s*\*\/)(?![\s\S]*?\b(license|copyright|permission)\b).+?\*\//is', // Matches docblocks, excludes licenses
            'todo'       => '/(?:\/\/|#|\/\*.*?\*\/).*\btodo\b:?.*/i', // Matches TODO comments, optional colon, case-insensitive
            'fixme'      => '/(?:\/\/|#|\/\*.*?\*\/).*\bfixme\b:?.*/i', // Matches FIXME comments, optional colon, case-insensitive
            'license'    => '/\/\*\*.*?\b(license|copyright|permission)\b.*?\*\//is' // Matches license information within docblocks
        ];

        // Array to store results
        $comments = [
            'singleLine' => [],
            'multiLine'  => [],
            'docBlock'   => [],
            'todo'       => [],
            'fixme'      => [],
            'license'    => [],
        ];

        // Apply regex patterns to find comments
        foreach ($patterns as $type => $pattern) {
            preg_match_all($pattern, $code, $matches);
            $comments[$type] = $matches[0];
        }

        return $comments;
    }

    private function countCommentLines(array $comments): array
    {
        $lineCounts = [];
        foreach ($comments as $type => $commentArray) {
            $lineCounts[$type] = 0;
            foreach ($commentArray as $comment) {
                // Count the number of newlines in each comment and add 1 for the line itself
                $lineCounts[$type] += substr_count($comment, "\n") + 1;
            }
        }
        $lineCounts['total'] = array_sum($lineCounts);
        return $lineCounts;
    }

    private function countTotalLines(string $filename): int
    {
        $fileContent = file($filename);
        return count($fileContent);
    }
}
