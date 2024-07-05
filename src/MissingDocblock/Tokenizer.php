<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use function is_array;

final readonly class Tokenizer extends TokenComparator
{
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

    public function isAnonymousClass(array $tokens, int $index): bool
    {
        return
            $this->isFollowingToken($tokens, $index, '(')
            || $this->isFollowingToken($tokens, $index, '{');
    }

    public function isAnonymousFunction(array $tokens, int $index): bool
    {
        return $this->isFollowingToken($tokens, $index, '(');
    }

    protected function isVisibilityModificator(mixed $token): bool
    {
        return is_array($token)
            && (
                $this->isPublic($token)
                || $this->isProtected($token)
                || $this->isPrivate($token)
            );
    }

    protected function isConstructPropertyDeclaration(array|string $token): bool
    {
        if (is_array($token)) {
            return ($token[1] === '__construct');
        }
        return ($token === '(' || $token === ',');
    }

    private function isWithinBounds(array $tokens, int $index, int $offset): bool
    {
        return isset($tokens[$index + $offset]);
    }

    public function isPropertyOrConstant(array $tokens, int $index): bool
    {
        $lineNumber = $tokens[$index][2];

        return $this->hasSemicolonOnSameLine($tokens, $index, $lineNumber)
            && $this->hasVisibilityModifierOnSameLine($tokens, $index, $lineNumber);
    }

    private function hasSemicolonOnSameLine(array $tokens, int $index, int $lineNumber): bool
    {
        $count = count($tokens);
        for ($i = $index + 1; $i < $count; $i++) {
            if ($tokens[$i] === ';') {
                return true;
            }
            if (is_array($tokens[$i]) && $tokens[$i][2] !== $lineNumber) {
                break;
            }
        }
        return false;
    }

    private function hasVisibilityModifierOnSameLine(array $tokens, int $index, int $lineNumber): bool
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            if (!is_array($tokens[$i]) || $tokens[$i][2] !== $lineNumber) {
                break;
            }
            if (
                ($this->isVisibilityModificator($tokens[$i]) || $this->isFinal($tokens[$i]))
                && !$this->isConstructPropertyDeclaration($tokens[$index])
            ) {
                return true;
            }
        }
        return false;
    }

    public function isClassNameResolution(array $tokens, int $index): bool
    {
        return (
            $this->isWithinBounds($tokens, $index, -1)
            && $this->isDoubleColon($tokens[$index - 1])
        );
    }

    public function isFunctionImport(array $tokens, int $index): bool
    {
        return (
            $this->isWithinBounds($tokens, $index, -1) && $this->isWhitespace($tokens[$index - 1])
            && $this->isWithinBounds($tokens, $index, -2) && $this->isUse($tokens[$index - 2])
        );
    }
}
