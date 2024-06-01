<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

use function array_keys;
use function array_map;
use function array_merge;
use function array_sum;
use function file;
use function file_get_contents;
use function in_array;
use function preg_match_all;
use function round;
use function substr_count;
use function token_get_all;

use const PHP_EOL;
use const T_CLASS;
use const T_DOC_COMMENT;
use const T_FUNCTION;

final class CommentDensity
{
    private bool $exceedThreshold = false;

    public function __construct(
        private readonly OutputInterface $output,
        private readonly array $thresholds
    ) {
    }

    public function checkForDocBlocks(string $filename): array
    {
        $code = file_get_contents($filename);
        $tokens = token_get_all($code);
        return $this->analyzeTokens($tokens);
    }

    private function analyzeTokens(array $tokens): array
    {
        $lastDocBlock = null;
        $results = ['classes' => [], 'methods' => []];

        foreach ($tokens as $token) {
            if ($token[0] === T_DOC_COMMENT) {
                $lastDocBlock = $token[1];
            } elseif ($token[0] === T_CLASS) {
                $name = $this->getNextNonWhitespaceToken($tokens, key($tokens));
                $results['classes'][$name] = ['hasDocBlock' => !empty($lastDocBlock)];
                $lastDocBlock = null;
            } elseif ($token[0] === T_FUNCTION) {
                $name = $this->getNextNonWhitespaceToken($tokens, key($tokens));
                $results['methods'][$name] = ['hasDocBlock' => !empty($lastDocBlock)];
                $lastDocBlock = null;
            }
        }

        return $results;
    }

    private function getNextNonWhitespaceToken(array $tokens, int $currentIndex): string
    {
        $count = count($tokens);
        for ($i = $currentIndex + 1; $i < $count; $i++) {
            if ($tokens[$i][0] !== T_WHITESPACE) {
                return $tokens[$i][1];
            }
        }
        return '';
    }

    public function analyzeDirectory(string $directory): bool
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        $commentStatistics = [];
        $totalLinesOfCode = 0;

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            // Check if the file is a PHP file
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filename = $file->getRealPath();
                if (! $file->isReadable()) {
                    $this->output->writeln("<highlight>$filename is not readable</highlight>");
                }
                $this->output->writeln("<info>Analyzing $filename</info>");
                $statistics = $this->getStatistics($filename);

                foreach ($statistics['commentStatistic'] as $type => $count) {
                    if (!isset($commentStatistics[$type])) {
                        $commentStatistics[$type] = 0;
                    }
                    $commentStatistics[$type] += $count;
                }

                $totalLinesOfCode += $statistics['linesOfCode'];
            }
        }

        $this->printStatistics($commentStatistics, $totalLinesOfCode);
        return $this->exceedThreshold;
    }

    private function getMissingDocblockStatistics(array $docBlocs): int
    {
        $missing = 0;
        foreach ($docBlocs['classes'] as $class) {
            if (! $class['hasDocBlock']) {
                $missing++;
            }
        }
        foreach ($docBlocs['methods'] as $method) {
            if (! $method['hasDocBlock']) {
                $missing++;
            }
        }
        return $missing;
    }

    private function getStatistics(string $filename): array
    {
        $comments = $this->getCommentsFromFile($filename);
        $missingDocBlocks = $this->getMissingDocblockStatistics($this->checkForDocBlocks($filename));
        $commentStatistic = $this->countCommentLines($comments);
        $commentStatistic['missingDocblock'] = $missingDocBlocks;
        $linesOfCode = $this->countTotalLines($filename);
        return [
            'commentStatistic' => $commentStatistic,
            'linesOfCode' => $linesOfCode,
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function printStatistics(array $commentStatistics, int $linesOfCode): void
    {
        $table = new Table($this->output);
        $table
            ->setHeaders(['Comment Type', 'Lines'])
            ->setRows(
                array_map(function (string $type, int $count): array {
                    $commentTypeColor = $this->getColorForCommentType(CommentType::tryFrom($type));
                    $color = $this->getColorForThresholds(CommentType::tryFrom($type), $count);
                    return ["<fg={$commentTypeColor}>{$type}</>", "<fg={$color}>{$count}</>"];
                }, array_keys($commentStatistics), $commentStatistics)
            );

        $table->render();
        $ratio = $this->getRatio($commentStatistics, $linesOfCode);
        $color = $this->getColorForRatio($ratio);
        $this->output->writeln(["<fg=$color>Com/LoC: $ratio</>"]);
    }

    private function getRatio(array $commentStatistics, int $linesOfCode): float
    {
        $totalComments = array_sum($commentStatistics);
        return round($totalComments / $linesOfCode, 2);
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
        if (! isset($this->thresholds[$type->value])) {
            return 'white';
        }

        if (in_array($type->value, ['docBlock', 'license'])) {
            if ($count >= $this->thresholds[$type->value]) {
                return 'green';
            }
            $this->exceedThreshold = true;
            return 'red';
        }

        if (in_array($type->value, ['regular', 'todo', 'fixme'])) {
            if ($count <= $this->thresholds[$type->value]) {
                return 'green';
            }
            $this->exceedThreshold = true;
            return 'red';
        }
    }

    private function getColorForCommentType(CommentType $type): string
    {
        return match ($type->value) {
            'docBlock' => 'green',
            'regular', 'missingDocblock' => 'red',
            'todo', 'fixme' => 'yellow',
            'license' => 'white',
        };
    }

    private function getCommentsFromFile(string $filename): array
    {
        $code = file_get_contents($filename);

        // Regex patterns for different types of comments
        $patterns = [
            'singleLine' =>
            // Matches // comments, excludes TODO/FIXME, case-insensitive
                '/\/\/(?!.*\b(?:todo|fixme)\b:?).*/i',
            'multiLine' =>
            // Matches /* */ comments, excludes /** */ and those containing TODO/FIXME, case-insensitive
                '/\/\*(?!\*|\s*\*.*\b(?:todo|fixme)\b:?).+?\*\//is',
            'docBlock' =>
            // Matches docblocks, excludes licenses
                '/\/\*\*(?!\s*\*\/)(?![\s\S]*?\b(license|copyright|permission)\b).+?\*\//is',
            'todo' =>
            // Matches TODO comments, optional colon, case-insensitive
                '/(?:\/\/|#|\/\*.*?\*\/).*\btodo\b:?.*/i',
            'fixme' =>
            // Matches FIXME comments, optional colon, case-insensitive
                '/(?:\/\/|#|\/\*.*?\*\/).*\bfixme\b:?.*/i',
            'license' =>
            // Matches license information within docblocks
                '/\/\*\*.*?\b(license|copyright|permission)\b.*?\*\//is'
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
