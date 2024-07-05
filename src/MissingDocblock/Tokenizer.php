<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use function is_array;

use const T_ARRAY;
use const T_CLASS;
use const T_CONST;
use const T_FINAL;
use const T_FUNCTION;
use const T_PAAMAYIM_NEKUDOTAYIM;
use const T_PRIVATE;
use const T_PROTECTED;
use const T_PUBLIC;
use const T_READONLY;
use const T_STATIC;
use const T_STRING;
use const T_VARIABLE;
use const T_WHITESPACE;

final readonly class Tokenizer
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

    protected function isWhitespace(array|string $token): bool
    {
        return is_array($token) && $token[0] === T_WHITESPACE;
    }

    protected function isTypeDeclaration(mixed $token): bool
    {
        return $this->isString($token) || $this->isArray($token);
    }

    protected function isArray(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_ARRAY;
    }

    protected function isString(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_STRING;
    }

    protected function isVisibilityModificator(mixed $token): bool
    {
        return is_array($token) && in_array($token[0], [T_PUBLIC, T_PROTECTED, T_PRIVATE], true);
    }

    protected function isStatic(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_STATIC;
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

    protected function isDoubleColon(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_PAAMAYIM_NEKUDOTAYIM;
    }

    public function isClassNameResolution(array $tokens, int $index): bool
    {
        return (
            $this->isWithinBounds($tokens, $index, -1)
            && $this->isDoubleColon($tokens[$index - 1])
        );
    }

    protected function isUse(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_USE;
    }

    public function isFunctionImport(array $tokens, int $index): bool
    {
        return (
            $this->isWithinBounds($tokens, $index, -1) && $this->isWhitespace($tokens[$index - 1])
            && $this->isWithinBounds($tokens, $index, -2) && $this->isUse($tokens[$index - 2])
        );
    }

    public function isClass(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_CLASS;
    }

    public function isFunction(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_FUNCTION;
    }

    public function isConst(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_CONST;
    }

    public function isVariable(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_VARIABLE;
    }

    public function isReadonly(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_READONLY;
    }

    public function isFinal(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_FINAL;
    }
}
