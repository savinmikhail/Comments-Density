<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

final class MissingDocBlockAnalyzer
{
    /**
     * Analyzes a file for missing docblocks.
     *
     * @param string $filename The filename to analyze.
     * @return array The analysis results.
     */
    private function checkForDocBlocks(string $filename): array
    {
        $code = file_get_contents($filename);
        $tokens = token_get_all($code);
        return $this->analyzeTokens($tokens);
    }

    /**
     * Analyzes the tokens of a file for docblocks.
     *
     * @param array $tokens The tokens to analyze.
     * @return array The analysis results.
     */
    private function analyzeTokens(array $tokens): array
    {
        $lastDocBlock = null;
        $results = ['classes' => [], 'methods' => []];
        $tokenCount = count($tokens);

        for ($i = 0; $i < $tokenCount; $i++) {
            $token = $tokens[$i];

            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_DOC_COMMENT) {
                $lastDocBlock = $token[1];
            } elseif (in_array($token[0], [T_CLASS, T_TRAIT, T_INTERFACE, T_ENUM], true)) {
                $name = $this->getNextNonWhitespaceToken($tokens, ++$i);
                $results['classes'][$name] = ['hasDocBlock' => !empty($lastDocBlock)];
                $lastDocBlock = null;
            } elseif ($token[0] === T_FUNCTION) {
                $name = $this->getNextNonWhitespaceToken($tokens, ++$i);
                $results['methods'][$name] = ['hasDocBlock' => !empty($lastDocBlock)];
                $lastDocBlock = null;
            }
        }

        return $results;
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

    /**
     * Gets the count of missing docblocks in a file.
     *
     * @param string $filename The filename to analyze.
     * @return int The count of missing docblocks.
     */
    public function getMissingDocblockStatistics(string $filename): int
    {
        $docBlocks = $this->checkForDocBlocks($filename);
        $missing = 0;
        foreach ($docBlocks['classes'] as $class) {
            if (! $class['hasDocBlock']) {
                $missing++;
            }
        }
        foreach ($docBlocks['methods'] as $method) {
            if (! $method['hasDocBlock']) {
                $missing++;
            }
        }
        return $missing;
    }
}
