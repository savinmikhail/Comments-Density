<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Comments;

use Stringable;

abstract class Comment
{
    protected bool $exceedThreshold = false;
    public function hasExceededThreshold(): bool
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
