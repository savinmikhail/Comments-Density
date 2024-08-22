<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Comments;

use Stringable;

abstract class Comment implements Stringable, CommentTypeInterface, CommentConstantsInterface
{
    protected bool $exceedThreshold = false;

    final public function hasExceededThreshold(): bool
    {
        return $this->exceedThreshold;
    }

    final public function matchesPattern(string $token): bool
    {
        return (bool) preg_match($this->getPattern(), $token);
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @param array<string, float> $thresholds
     */
    final public function getStatColor(int $count, array $thresholds): string
    {
        if (!isset($thresholds[$this->getName()])) {
            return 'white';
        }
        if ($this->isWithinThreshold($count, $thresholds)) {
            return 'green';
        }
        $this->exceedThreshold = true;

        return 'red';
    }

    final public function getPattern(): string
    {
        return static::PATTERN;
    }

    final public function getColor(): string
    {
        return static::COLOR;
    }

    final public function getWeight(): float
    {
        return static::WEIGHT;
    }

    final public function getName(): string
    {
        return static::NAME;
    }

    /** @param array<string, float> $thresholds */
    protected function isWithinThreshold(int $count, array $thresholds): bool
    {
        $comparisonValue = $thresholds[static::NAME];

        if (static::COMPARISON_TYPE === '>=') {
            return $count >= $comparisonValue;
        }

        return $count <= $comparisonValue;
    }
}
