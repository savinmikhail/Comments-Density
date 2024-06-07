<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

final class MissingDocBlockAnalyzer
{
    /**
     * Analyzes the tokens of a file for docblocks.
     *
     * @param array $tokens The tokens to analyze.
     * @return array The analysis results.
     */
    private function analyzeTokens(array $tokens, string $filename): array
    {
        $lastDocBlock = null;
        $missingDocBlocks = [];
        $tokenCount = count($tokens);

        for ($i = 0; $i < $tokenCount; $i++) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_DOC_COMMENT) {
                $lastDocBlock = $token[1];
            } elseif (in_array($token[0], [T_CLASS, T_TRAIT, T_INTERFACE, T_ENUM, T_FUNCTION], true)) {
                $this->getNextNonWhitespaceToken($tokens, ++$i);
                if (empty($lastDocBlock)) {
                    $missingDocBlocks[] = [
                        'type' => 'missingDocblock',
                        'content' => '',//"$name missing docblock",
                        'file' => $filename,
                        'line' => $token[2]
                    ];
                }
                $lastDocBlock = null;
            }
        }

        return $missingDocBlocks;
    }

    /**
     * Gets the next non-whitespace token.
     *
     * @param array $tokens The tokens to analyze.
     * @param int $currentIndex The current index in the tokens array.
     * @return string The next non-whitespace token.
     */
    private function getNextNonWhitespaceToken(array $tokens, int $currentIndex): string
    {
        $count = count($tokens);
        for ($i = $currentIndex; $i < $count; $i++) {
            if (! is_array($tokens[$i])) {
                continue;
            }
            if ($tokens[$i][0] !== T_WHITESPACE) {
                return $tokens[$i][1];
            }
        }
        return '';
    }

    public function getMissingDocblocks(array $tokens, string $filename): array
    {
        return $this->analyzeTokens($tokens, $filename);
    }
}
