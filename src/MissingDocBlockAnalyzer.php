<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

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
            if ($this->isWhitespace($nextToken)) {
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
            if ($this->isAnonymousClass($tokens, $index) || $this->isClassNameResolution($tokens, $index)) {
                return false;
            }
        }

        if ($token[0] === T_FUNCTION) {
            if ($this->isAnonymousFunction($tokens, $index) ||  $this->isFunctionImport($tokens, $index)) {
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

    protected function isWhitespace(array|string $token): bool
    {
        if (! is_array($token)) {
            return false;
        }
        return $token[0] === T_WHITESPACE;
    }

    protected function isTypeDeclaration(mixed $token): bool
    {
        if (! is_array($token)) {
            return false;
        }
        return $token[0] === T_STRING || $token[0] === T_ARRAY;
    }

    protected function isVisibilityModificator(mixed $token): bool
    {
        if (! is_array($token)) {
            return false;
        }
        return  $token[0] === T_PUBLIC
            || $token[0] === T_PROTECTED
            || $token[0] === T_PRIVATE;
    }

    protected function isStatic(mixed $token): bool
    {
        if (! is_array($token)) {
            return false;
        }
        return $token[0] === T_STATIC;
    }

    protected function isConstructPropertyDeclaration(array|string $token): bool
    {
        if (is_array($token)) {
            return false;
        }
        return ($token === '(' || $token === ',');
    }

    private function isWithinBounds(array $tokens, int $index, int $offset): bool
    {
        return isset($tokens[$index + $offset]);
    }

    private function isPropertyOrConstant(array $tokens, int $index): bool
    {
        return (
            $this->isWithinBounds($tokens, $index, -2)
            &&  (
                $this->isTypeDeclaration($tokens[$index - 2])
                || $this->isVisibilityModificator($tokens[$index - 2])
                || $this->isStatic($tokens[$index - 2])
                || $tokens[$index - 2][0] === T_READONLY
                || $tokens[$index - 2][0] === T_FINAL
            )
            && ! (
                $this->isWithinBounds($tokens, $index, -4)
                && $this->isConstructPropertyDeclaration($tokens[$index - 4])
            )
        );
    }

    protected function isClassNameResolution(array $tokens, int $index): bool
    {
        return (
            $this->isWithinBounds($tokens, $index, -2)
            && $tokens[$index - 2][0] === T_STRING
            && $tokens[$index - 1][0] === T_PAAMAYIM_NEKUDOTAYIM
        );
    }

    private function isFunctionImport(array $tokens, int $index): bool
    {
        return (
            $this->isWithinBounds($tokens, $index, -1) && $this->isWhitespace($tokens[$index - 1])
            && $this->isWithinBounds($tokens, $index, -2) && $tokens[$index - 2][0] === T_USE
        );
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
