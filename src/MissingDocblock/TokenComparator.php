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
use const T_STATIC;
use const T_VARIABLE;
use const T_WHITESPACE;

readonly class TokenComparator
{
    protected function isWhitespace(array|string $token): bool
    {
        return is_array($token) && $token[0] === T_WHITESPACE;
    }

    protected function isArray(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_ARRAY;
    }

    protected function isStatic(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_STATIC;
    }

    protected function isDoubleColon(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_PAAMAYIM_NEKUDOTAYIM;
    }

    protected function isUse(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_USE;
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

    protected function isFinal(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_FINAL;
    }

    protected function isPublic(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_PUBLIC;
    }

    protected function isProtected(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_PROTECTED;
    }

    protected function isPrivate(mixed $token): bool
    {
        return is_array($token) && $token[0] === T_PRIVATE;
    }
}
