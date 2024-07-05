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
        return $this->isFollowingToken($tokens, $index, '{');
    }

    public function isAnonymousFunction(array $tokens, int $index): bool
    {
        return $this->isFollowingToken($tokens, $index, '(');
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
        return $token[0] === T_PUBLIC
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
        return (
            $this->isWhitespace($tokens[$index - 1])
            && $this->isWithinBounds($tokens, $index, -2)
            &&  (
                $this->isTypeDeclaration($tokens[$index - 2])
                || $this->isVisibilityModificator($tokens[$index - 2])
                || $this->isStatic($tokens[$index - 2])
                || $this->isReadonly($tokens[$index - 2])
                || $this->isFinal($tokens[$index - 2])
            )
            && ! (
                $this->isWithinBounds($tokens, $index, -4)
                && $this->isConstructPropertyDeclaration($tokens[$index - 4])
            )
        );
    }

    public function isClassNameResolution(array $tokens, int $index): bool
    {
        return (
            $this->isWithinBounds($tokens, $index, -1)
            && $tokens[$index - 1][0] === T_PAAMAYIM_NEKUDOTAYIM
        );
    }

    public function isFunctionImport(array $tokens, int $index): bool
    {
        return (
            $this->isWithinBounds($tokens, $index, -1) && $this->isWhitespace($tokens[$index - 1])
            && $this->isWithinBounds($tokens, $index, -2) && $tokens[$index - 2][0] === T_USE
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
