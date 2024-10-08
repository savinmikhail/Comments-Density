<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Analyzer;

use SavinMikhail\CommentsDensity\Cache\Cache;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\MissingDocblock\MissingDocBlockAnalyzer;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;

use function array_merge;
use function count;
use function file;
use function file_get_contents;
use function in_array;
use function is_array;
use function token_get_all;

use const T_COMMENT;
use const T_DOC_COMMENT;

final readonly class AnalyzeFileTask
{
    public function __construct(
        private Cache $cache,
        private MissingDocBlockAnalyzer $docBlockAnalyzer,
        private MissingDocBlockAnalyzer $missingDocBlock,
        private CommentFactory $commentFactory,
        private ConfigDTO $configDTO,
        private OutputInterface $output,
    ) {}

    /**
     * @return array{'lines': int, 'comments': array<array-key, array<string, int>>}
     */
    public function run(SplFileInfo $file): array
    {
        if ($this->shouldSkipFile($file)) {
            return ['lines' => 0, 'comments' => []];
        }

        $fileComments = $this->cache->getCache($file->getRealPath());

        if (!$fileComments) {
            $fileComments = $this->analyzeFile($file->getRealPath());
            $this->cache->setCache($file->getRealPath(), $fileComments);
        }

        $totalLinesOfCode = $this->countTotalLines($file->getRealPath());

        return ['lines' => $totalLinesOfCode, 'comments' => $fileComments];
    }

    private function shouldSkipFile(SplFileInfo $file): bool
    {
        return
            $this->isInWhitelist($file->getRealPath())
            || $file->getSize() === 0
            || !$this->isPhpFile($file)
            || !$file->isReadable();
    }

    /**
     * @return CommentDTO[]
     */
    private function analyzeFile(string $filename): array
    {
        $this->output->writeln("<info>Analyzing {$filename}</info>");

        $code = file_get_contents($filename);
        $tokens = token_get_all($code);

        $comments = $this->getCommentsFromFile($tokens, $filename);
        if ($this->shouldAnalyzeMissingDocBlocks()) {
            $missingDocBlocks = $this->docBlockAnalyzer->getMissingDocblocks($code, $filename);
            $comments = array_merge($missingDocBlocks, $comments);
        }

        return $comments;
    }

    private function shouldAnalyzeMissingDocBlocks(): bool
    {
        return
            empty($this->configDTO->only)
            || in_array($this->missingDocBlock->getName(), $this->configDTO->only, true);
    }

    /**
     * @param array<mixed> $tokens
     * @return CommentDTO[]
     */
    private function getCommentsFromFile(array $tokens, string $filename): array
    {
        $comments = [];
        foreach ($tokens as $token) {
            if (!is_array($token) || !in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $commentType = $this->commentFactory->classifyComment($token[1]);
            if ($commentType) {
                $comments[] =
                    new CommentDTO(
                        $commentType->getName(),
                        $commentType->getColor(),
                        $filename,
                        $token[2],
                        $token[1],
                    );
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

    private function isInWhitelist(string $filePath): bool
    {
        foreach ($this->configDTO->exclude as $whitelistedDir) {
            if (str_contains($filePath, $whitelistedDir)) {
                return true;
            }
        }

        return false;
    }
}
