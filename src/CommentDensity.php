<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SavinMikhail\CommentsDensity\Comments\Comment;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

use function array_keys;
use function array_map;
use function array_sum;
use function file;
use function file_get_contents;
use function preg_match_all;
use function round;
use function substr_count;

use const PHP_EOL;

final class CommentDensity
{
    private bool $exceedThreshold = false;

    public function __construct(
        private readonly OutputInterface $output,
        private readonly array $thresholds,
        private readonly MissingDocBlockAnalyzer $docBlockAnalyzer,
        private readonly StatisticCalculator $statisticCalculator
    ) {
    }

    public function analyzeDirectory(string $directory): bool
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        $commentStatistics = [];
        $totalLinesOfCode = 0;
        $cdsSum = 0;
        $filesAnalyzed = 0;
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            // Check if the file is a PHP file
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $filename = $file->getRealPath();
            if (! $file->isReadable()) {
                $this->output->writeln("<highlight>$filename is not readable</highlight>");
                continue;
            }
            $this->output->writeln("<info>Analyzing $filename</info>");
            $statistics = $this->getStatistics($filename);
            foreach ($statistics['commentStatistic'] as $type => $count) {
                if (! isset($commentStatistics[$type])) {
                    $commentStatistics[$type] = 0;
                }
                $commentStatistics[$type] += $count;
            }

            $totalLinesOfCode += $statistics['linesOfCode'];
            $cdsSum += $statistics['CDS'];
            $filesAnalyzed++;
        }
        $this->printStatistics($commentStatistics, $totalLinesOfCode, $cdsSum / $filesAnalyzed);
        return $this->exceedThreshold;
    }

    private function getStatistics(string $filename): array
    {
        $comments = $this->getCommentsFromFile($filename);
        $missingDocBlocks = $this
            ->docBlockAnalyzer
            ->getMissingDocblockStatistics($filename);
        $commentStatistic = $this->countCommentLines($comments);
        $commentStatistic['missingDocblock'] = $missingDocBlocks;
        $linesOfCode = $this->countTotalLines($filename);
        $cds = $this
            ->statisticCalculator
            ->calculateCDS($commentStatistic);

        return [
            'commentStatistic' => $commentStatistic,
            'linesOfCode' => $linesOfCode,
            'CDS' => $cds
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function printStatistics(array $commentStatistics, int $linesOfCode, float $cds): void
    {
        $table = new Table($this->output);
        $table
            ->setHeaders(['Comment Type', 'Lines'])
            ->setRows(
                array_map(function (string $type, int $count): array {
                    $commentTypeColor = $this->getColorForCommentType(CommentType::tryFrom($type));
                    $color = $this->getColorForThresholds(CommentType::tryFrom($type), $count);
                    return ["<fg=$commentTypeColor>$type</>", "<fg=$color>$count</>"];
                }, array_keys($commentStatistics), $commentStatistics)
            );

        $table->render();
        $ratio = $this->getRatio($commentStatistics, $linesOfCode);
        $color = $this->getColorForRatio($ratio);
        $this->output->writeln(["<fg=$color>Com/LoC: $ratio</>"]);
        $color = $this->getColorForCDS($cds);
        $this->output->writeln(["<fg=$color>CDS: $cds</>"]);
    }

    private function getRatio(array $commentStatistics, int $linesOfCode): float
    {
        $totalComments = array_sum($commentStatistics);
        return round($totalComments / $linesOfCode, 2);
    }

    private function getColorForCDS(float $cds): string
    {
        if (! isset($this->thresholds['CDS'])) {
            return 'white';
        }
        if ($cds >= $this->thresholds['CDS']) {
            return 'green';
        }
        $this->exceedThreshold = true;
        return 'red';
    }

    private function getColorForRatio(float $ratio): string
    {
        if (! isset($this->thresholds['Com/LoC'])) {
            return 'white';
        }
        if ($ratio >= $this->thresholds['Com/LoC']) {
            return 'green';
        }
        $this->exceedThreshold = true;
        return 'red';
    }

    private function getColorForThresholds(CommentType $type, int $count): string
    {
        $commentTypes = Comment::getTypes();
        foreach ($commentTypes as $commentType) {
            if ($type->value === $commentType->getName()) {
                return $commentType->getStatColor($count, $this->thresholds);
            }
        }
        return 'white';
    }

    private function getColorForCommentType(CommentType $type): string
    {
        $commentTypes = Comment::getTypes();
        foreach ($commentTypes as $commentType) {
            if ($commentType->getName() === $type->value) {
                return $commentType->getColor();
            }
        }
        return 'white';
    }

    private function getCommentsFromFile(string $filename): array
    {
        $code = file_get_contents($filename);

        $commentTypes = Comment::getTypes();
        $patterns = [];
        foreach ($commentTypes as $commentType) {
            $patterns[$commentType->getName()] = $commentType->getPattern();
        }

        $comments = [];

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
