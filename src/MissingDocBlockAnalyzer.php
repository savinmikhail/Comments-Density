<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

final readonly class MissingDocBlockAnalyzer
{
    private function checkForDocBlocks(string $filename): array
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
            } elseif (in_array($token[0], [T_CLASS, T_TRAIT, T_INTERFACE, T_ENUM])) {
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

    public function getMissingDocblockStatistics(string $filename): int
    {
        $docBlocs = $this->checkForDocBlocks($filename);
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
}
