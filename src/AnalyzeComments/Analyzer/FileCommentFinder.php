<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer;

use Psr\Cache\InvalidArgumentException;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConfigDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\MissingDocblock\MissingDocBlockAnalyzer;
use SplFileInfo;
use Symfony\Contracts\Cache\CacheInterface;

use function array_merge;
use function count;
use function file;
use function file_get_contents;
use function in_array;
use function is_array;
use function token_get_all;

use const T_COMMENT;
use const T_DOC_COMMENT;

final readonly class FileCommentFinder
{
    public function __construct(
        private CacheInterface $cache,
        private CommentFactory $commentFactory,
        private ConfigDTO $configDTO,
        private MissingDocBlockAnalyzer $missingDocBlockAnalyzer
    ) {}

    /**
     * @return array{'lines': int, 'comments': array<array-key, array<string, int>>}
     * @throws InvalidArgumentException
     */
    public function run(SplFileInfo $file): array
    {
        if ($this->shouldSkipFile($file)) {
            return ['lines' => 0, 'comments' => []];
        }

        $filePath = $file->getRealPath();
        $lastModified = filemtime($filePath);
        $cacheKey = md5($filePath . $lastModified);

        $fileComments = $this->cache->get(
            $cacheKey,
            fn(): iterable => $this->analyzeFile($filePath),
        );

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
        $code = file_get_contents($filename);
        $tokens = token_get_all($code);

        $comments = $this->getCommentsFromFile($tokens, $filename);
        if ($this->shouldAnalyzeMissingDocBlocks()) {
            $missingDocBlocks = $this->missingDocBlockAnalyzer->getMissingDocblocks($code, $filename);
            $comments = array_merge($missingDocBlocks, $comments);
        }

        return $comments;
    }

    private function shouldAnalyzeMissingDocBlocks(): bool
    {
        return
            $this->configDTO->getAllowedTypes() === []
            || in_array(
                $this->missingDocBlockAnalyzer->getName(),
                $this->configDTO->getAllowedTypes(),
                true,
            );
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
