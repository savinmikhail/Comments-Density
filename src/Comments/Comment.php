<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Comments;

abstract class Comment implements \Stringable
{
    protected bool $exceedThreshold = false;

    public function isExceededThreshold(): bool
    {
        return $this->exceedThreshold;
    }

    public function matchesPattern(string $token): bool
    {
        return (bool) preg_match($this->getPattern(), $token);
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
