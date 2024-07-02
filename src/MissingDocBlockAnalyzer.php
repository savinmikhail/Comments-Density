<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use function in_array;
use function is_array;

use const T_CLASS;
use const T_DOC_COMMENT;
use const T_FUNCTION;
use const T_INTERFACE;
use const T_PAAMAYIM_NEKUDOTAYIM;
use const T_STRING;
use const T_TRAIT;
use const T_WHITESPACE;

final class MissingDocBlockAnalyzer
{
    private bool $exceedThreshold = false;

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
            } elseif (in_array($token[0], [T_CLASS, T_TRAIT, T_INTERFACE, T_FUNCTION], true)) {
                if ($token[0] === T_CLASS) {
                    $isClosure = $this->isClosure($tokens, $i);
                    if ($isClosure) {
                        continue;
                    }
                    $isAnonymousClass = $this->isAnonymousClass($tokens, $i);
                    if ($isAnonymousClass) {
                        continue;
                    }
                }
                if ($token[0] === T_FUNCTION) {

                    $isAnonymousFunction = $this->isAnonymousFunction($tokens, $i);
                    if ($isAnonymousFunction) {
                        continue;
                    }
                    if (! $this->isFunctionDeclaration($tokens, $i)) {
                        continue;
                    }
                }

                if (empty($lastDocBlock)) {
                    $missingDocBlocks[] = [
                        'type' => 'missingDocblock',
                        'content' => '',
                        'file' => $filename,
                        'line' => $token[2]
                    ];
                }
                $lastDocBlock = null;
            }
        }

        return $missingDocBlocks;
    }

    protected function isClosure(array $tokens, int $index): bool
    {
        $tokenCount = count($tokens);
        for ($j = $index + 1; $j < $tokenCount; $j++) {
            $nextToken = $tokens[$j];
            if ($nextToken[0] === T_WHITESPACE) {
                continue;
            }
            if (
                $tokens[$index - 3] === '['
                && $tokens[$index - 2][0] === T_STRING
                && $tokens[$index - 1][0] === T_PAAMAYIM_NEKUDOTAYIM
                && $tokens[$index][0] === T_CLASS
                && ($tokens[$index + 1] === ']' || $tokens[$index + 1] === ',')
            ) {
                return true;
            }
        }
        return false;
    }

    private function isAnonymousClass(array $tokens, int $index): bool
    {
        $tokenCount = count($tokens);
        for ($j = $index + 1; $j < $tokenCount; $j++) {
            $nextToken = $tokens[$j];
            if ($nextToken[0] === T_WHITESPACE) {
                continue;
            }
            return
                $tokens[$index][0] === T_CLASS
                && $nextToken === '{'
           ;
        }
        return false;
    }

    private function isAnonymousFunction(array $tokens, int $index): bool
    {
        $tokenCount = count($tokens);
        for ($j = $index + 1; $j < $tokenCount; $j++) {
            $nextToken = $tokens[$j];
            if ($nextToken[0] === T_WHITESPACE) {
                continue;
            }

           return $nextToken === '(';
        }
        return false;
    }

    private function isFunctionDeclaration(array $tokens, int $index): bool
    {
        $tokenCount = count($tokens);
        for ($j = $index + 1; $j < $tokenCount; $j++) {
            $nextToken = $tokens[$j];
            if (is_array($nextToken) && $nextToken[0] === T_STRING) {
                continue;
            }
            if ($nextToken === '(') {
                return true;
            }
            if ($nextToken === ';') {
                return false;
            }
        }
        return false;
    }

    public function getMissingDocblocks(array $tokens, string $filename): array
    {
        return $this->analyzeTokens($tokens, $filename);
    }

    public function getColor(): string
    {
        return 'red';
    }

    public function getStatColor(float $count, array $thresholds): string
    {
        if (! isset($thresholds['missingDocBlock'])) {
            return 'white';
        }
        if ($count <= $thresholds['missingDocBlock']) {
            return 'green';
        }
        $this->exceedThreshold = true;
        return 'red';
    }

    public function hasExceededThreshold(): bool
    {
        return $this->exceedThreshold;
    }

    public function getName(): string
    {
        return 'missingDocblock';
    }
}
