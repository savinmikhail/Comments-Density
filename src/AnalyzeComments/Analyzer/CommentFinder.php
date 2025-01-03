<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer;

use PhpToken;
use Psr\Cache\InvalidArgumentException;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\CommentTypeFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConfigDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\MissingDocblock\MissingDocBlockAnalyzer;
use SplFileInfo;
use Symfony\Contracts\Cache\CacheInterface;

use function array_merge;
use function file_get_contents;
use function in_array;
use function is_array;
use function token_get_all;

use const T_COMMENT;
use const T_DOC_COMMENT;

final readonly class CommentFinder
{
    public function __construct(
        private CommentTypeFactory      $commentFactory,
        private ConfigDTO               $configDTO,
        private MissingDocBlockAnalyzer $missingDocBlockAnalyzer
    ) {}

    /**
     * @return CommentDTO[]
     */
    public function run(string $content, string $filename): array
    {
        $tokens = PhpToken::tokenize($content);

        $comments = $this->getCommentsFromFile($tokens, $filename);
        if ($this->shouldAnalyzeMissingDocBlocks()) {
            $missingDocBlocks = $this->missingDocBlockAnalyzer->getMissingDocblocks($content, $filename);
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
     * @param PhpToken[] $tokens
     * @param string $filename
     * @return CommentDTO[]
     */
    private function getCommentsFromFile(array $tokens, string $filename): array
    {
        $comments = [];
        foreach ($tokens as $token) {
            if ($token->is([T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }
            $commentType = $this->commentFactory->classifyComment($token->text);
            if ($commentType) {
                $comments[] =
                    new CommentDTO(
                        $commentType->getName(),
                        $commentType->getColor(),
                        $filename,
                        $token->line,
                        $token->text,
                    );
            }
        }

        return $comments;
    }
}
