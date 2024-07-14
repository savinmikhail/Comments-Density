<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use Generator;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\Comments\CommentTypeInterface;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\DTO\Output\OutputDTO;
use SavinMikhail\CommentsDensity\Metrics\MetricsFacade;
use SavinMikhail\CommentsDensity\MissingDocblock\MissingDocBlockAnalyzer;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;

use function array_merge;
use function array_push;
use function file;
use function file_get_contents;
use function in_array;
use function is_array;
use function str_contains;
use function substr_count;
use function token_get_all;

use const PHP_EOL;
use const T_COMMENT;
use const T_DOC_COMMENT;

final class Analyzer
{
    private bool $exceedThreshold = false;

    public function __construct(
        private readonly ConfigDTO $configDTO,
        private readonly CommentFactory $commentFactory,
        private readonly MissingDocBlockAnalyzer $missingDocBlock,
        private readonly MetricsFacade $metrics,
        private readonly OutputInterface $output,
        private readonly MissingDocBlockAnalyzer $docBlockAnalyzer,
        private readonly BaselineManager $baselineManager,
    ) {
    }

    public function analyze(Generator $files): OutputDTO
    {
        $this->metrics->startPerformanceMonitoring();
        $comments = [];
        $totalLinesOfCode = 0;
        $filesAnalyzed = 0;

        foreach ($files as $file) {
            if (!($file instanceof SplFileInfo)) {
                continue;
            }
            if ($this->isInWhitelist($file->getRealPath())) {
                continue;
            }
            if ($file->getSize() === 0) {
                continue;
            }
            if (!$this->isPhpFile($file) || !$file->isReadable()) {
                continue;
            }

            $commentsAndLines = $this->analyzeFile($file->getRealPath());
            $totalLinesOfCode += $commentsAndLines['linesOfCode'];
            array_push($comments, ...$commentsAndLines['comments']);

            $filesAnalyzed++;
        }
        if ($this->configDTO->useBaseline) {
            $comments = $this->filterBaselineComments($comments);
        }

        $commentStatistics = $this->countCommentOccurrences($comments);

        $this->metrics->stopPerformanceMonitoring();

        return $this->createOutputDTO(
            $comments,
            $commentStatistics,
            $totalLinesOfCode,
            $this
                ->metrics
                ->calculateCDS($commentStatistics),
            $filesAnalyzed,
        );
    }

    public function analyzeFile(
        string $filename,
    ): array {
        $this->output->writeln("<info>Analyzing $filename</info>");

        $code = file_get_contents($filename);
        $tokens = token_get_all($code);

        $comments = $this->getCommentsFromFile($tokens, $filename);
        if (
            empty($this->configDto->only)
            || in_array('missingDocblock', $this->configDto->only, true)
        ) {
            $missingDocBlocks = $this
                ->docBlockAnalyzer
                ->getMissingDocblocks($code, $filename);
            $comments = array_merge($missingDocBlocks, $comments);
        }

        $linesOfCode = $this->countTotalLines($filename);

        return [
            'comments' => $comments,
            'linesOfCode' => $linesOfCode,
        ];
    }

    private function getCommentsFromFile(array $tokens, string $filename): array
    {
        $comments = [];
        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }
            if (!in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }
            $commentType = $this->commentFactory->classifyComment($token[1]);
            if ($commentType) {
                $comments[] = [
                    'content' => $token[1],
                    'type' => $commentType,
                    'line' => $token[2],
                    'file' => $filename
                ];
            }
        }
        return $comments;
    }

    private function countTotalLines(string $filename): int
    {
        $fileContent = file($filename);
        return count($fileContent);
    }

    private function isPhpFile(SplFileInfo $file): bool
    {
        return $file->isFile() && $file->getExtension() === 'php';
    }

    private function countCommentOccurrences(array $comments): array
    {
        $lineCounts = [];
        foreach ($comments as $comment) {
            $typeName = (string)$comment['type'];
            if (!isset($lineCounts[$typeName])) {
                $lineCounts[$typeName] = [
                    'lines' => 0,
                    'count' => 0,
                ];
            }
            $lineCounts[$typeName]['lines'] += substr_count($comment['content'], PHP_EOL) + 1;
            $lineCounts[$typeName]['count']++;
        }
        return $lineCounts;
    }

    private function createOutputDTO(
        array $comments,
        array $commentStatistics,
        int $linesOfCode,
        float $cds,
        int $filesAnalyzed,
    ): OutputDTO {
        $preparedStatistics = $this->prepareCommentStatistics($commentStatistics);
        $preparedComments = $this->prepareComments($comments);
        $performanceMetrics = $this->metrics->getPerformanceMetrics();
        $comToLoc = $this->metrics->prepareComToLoc($commentStatistics, $linesOfCode);
        $cds = $this->metrics->prepareCDS($cds);
        if ($this->metrics->hasExceededThreshold()) {
            $this->exceedThreshold = true;
        }
        return new OutputDTO(
            $filesAnalyzed,
            $preparedStatistics,
            $preparedComments,
            $performanceMetrics,
            $comToLoc,
            $cds,
            $this->exceedThreshold
        );
    }

    private function prepareCommentStatistics(array $commentStatistics): array
    {
        $preparedStatistics = [];
        foreach ($commentStatistics as $type => $stat) {
            if ($type === 'missingDocblock') {
                $preparedStatistics[] = new CommentStatisticsDTO(
                    $this->missingDocBlock->getColor(),
                    $this->missingDocBlock->getName(),
                    $stat['lines'],
                    $this->missingDocBlock->getStatColor($stat['count'], $this->configDTO->thresholds),
                    $stat['count']
                );
                if ($this->missingDocBlock->hasExceededThreshold()) {
                    $this->exceedThreshold = true;
                }
                continue;
            }
            $commentType = $this->commentFactory->getCommentType($type);
            if ($commentType) {
                $preparedStatistics[] = new CommentStatisticsDTO(
                    $commentType->getColor(),
                    $commentType->getName(),
                    $stat['lines'],
                    $commentType->getStatColor($stat['count'], $this->configDTO->thresholds),
                    $stat['count']
                );
                if ($commentType->hasExceededThreshold()) {
                    $this->exceedThreshold = true;
                }
            }
        }
        return $preparedStatistics;
    }

    private function prepareComments(array $comments): array
    {
        $preparedComments = [];
        foreach ($comments as $comment) {
            /** @var CommentTypeInterface|string $commentType */
            $commentType = $comment['type'];
            if ($commentType === 'missingDocblock') {
                $preparedComments[] = new CommentDTO(
                    'missingDocblock',
                    'red',
                    $comment['file'],
                    $comment['line'],
                    $comment['content']
                );
                continue;
            }
            if ($commentType->getWeight() > 0) {
                continue;
            }
            $preparedComments[] = new CommentDTO(
                $commentType->getName(),
                $commentType->getColor(),
                $comment['file'],
                $comment['line'],
                $comment['content']
            );
        }
        return $preparedComments;
    }

    private function isInWhitelist(string $filePath): bool
    {
        foreach ($this->configDTO->exclude as $whitelistedDir) {
            if (str_contains($filePath, $whitelistedDir)) {
                return true;
            }
        }
        return false;
    }

    private function filterBaselineComments(array $comments): array
    {
        $baselineComments = $this->baselineManager->getAllComments();
        if (empty($baselineComments)) {
            return $comments;
        }

        $baselineCommentKeys = array_map(
            fn($comment) => $comment['file_path'] . ':' . $comment['line_number'],
            $baselineComments
        );

        return array_filter(
            $comments,
            fn($comment) => !in_array(
                $comment['file'] . ':' . $comment['line'],
                $baselineCommentKeys,
                true
            )
        );
    }
}
