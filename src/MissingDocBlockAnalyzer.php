<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use function dd;
use function in_array;
use function is_array;

use const T_ARRAY;
use const T_CLASS;
use const T_CONST;
use const T_DOC_COMMENT;
use const T_FINAL;
use const T_FUNCTION;
use const T_INTERFACE;
use const T_PAAMAYIM_NEKUDOTAYIM;
use const T_PRIVATE;
use const T_PROTECTED;
use const T_PUBLIC;
use const T_READONLY;
use const T_STATIC;
use const T_STRING;
use const T_TRAIT;
use const T_ENUM;
use const T_VARIABLE;
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

            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_DOC_COMMENT) {
                $lastDocBlock = $token[1];
            } elseif ($this->isDocBlockRequired($token, $tokens, $i)) {
                if (empty($lastDocBlock)) {
                    $missingDocBlocks[] = $this->createMissingDocBlockStat($token, $filename);
                }
                $lastDocBlock = null;
            }
        }

        return $missingDocBlocks;
    }

    private function isFollowingToken(array $tokens, int $index, string $expectedToken): bool
    {
        for ($j = $index + 1, $count = count($tokens); $j < $count; $j++) {
            $nextToken = $tokens[$j];
            if ($nextToken[0] === T_WHITESPACE) {
                continue;
            }
            return $nextToken === $expectedToken;
        }
        return false;
    }

    private function isAnonymousClass(array $tokens, int $index): bool
    {
        return $this->isFollowingToken($tokens, $index, '{');
    }

    private function isAnonymousFunction(array $tokens, int $index): bool
    {
        return $this->isFollowingToken($tokens, $index, '(');
    }

    private function isDocBlockRequired(array $token, array $tokens, int $index): bool
    {
        if (! in_array($token[0], [T_CLASS, T_TRAIT, T_INTERFACE, T_ENUM, T_FUNCTION, T_CONST, T_VARIABLE], true)) {
            return false;
        }
        if ($token[0] === T_CLASS) {
            if ($this->isAnonymousClass($tokens, $index) || $this->isClosure($tokens, $index)) {
                return false;
            }
        }

        if ($token[0] === T_FUNCTION) {
            if ($this->isAnonymousFunction($tokens, $index) || ! $this->isFunctionDeclaration($tokens, $index)) {
                return false;
            }
        }

        if ($token[0] === T_CONST || $token[0] === T_VARIABLE) {
            if (! $this->isPropertyOrConstant($tokens, $index)) {
                return false;
            }
        }

        return true;
    }

    private function createMissingDocBlockStat(array $token, string $filename): array
    {
        return [
            'type' => 'missingDocblock',
            'content' => '',
            'file' => $filename,
            'line' => $token[2]
        ];
    }

    private function isPropertyOrConstant(array $tokens, int $index): bool
    {
        return (
            $tokens[$index - 1][0] === T_WHITESPACE
            && (
                $tokens[$index - 2][0] === T_STRING
                || $tokens[$index - 2][0] === T_ARRAY
                || $tokens[$index - 2][0] === T_STATIC
                || $tokens[$index - 2][0] === T_READONLY
                || $tokens[$index - 2][0] === T_PUBLIC
                || $tokens[$index - 2][0] === T_PROTECTED
                || $tokens[$index - 2][0] === T_PRIVATE
                || $tokens[$index - 2][0] === T_FINAL
            )
            && (
                $tokens[$index - 3][0] === T_WHITESPACE
            )
            && ($tokens[$index - 4] !== '(' && $tokens[$index - 4] !== ',')
        );
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
                $tokens[$index - 2][0] === T_STRING
                && $tokens[$index - 1][0] === T_PAAMAYIM_NEKUDOTAYIM
                && $tokens[$index][0] === T_CLASS
            ) {
                return true;
            }
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
