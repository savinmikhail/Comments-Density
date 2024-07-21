<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Comments;

use Stringable;

abstract class Comment implements Stringable, CommentTypeInterface, CommentConstantsInterface
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

    /**
     * @param int $count
     * @param array<string, float> $thresholds
     *
     * @return string
     */
    public function getStatColor(int $count, array $thresholds): string
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

    protected function isWithinThreshold(int $count, array $thresholds): bool
    {
        $comparisonValue = $thresholds[static::NAME];

        if (static::COMPARISON_TYPE === '>=') {
            return $count >= $comparisonValue;
        }

        return $count <= $comparisonValue;
    }

    public function getPattern(): string
    {
        return static::PATTERN;
    }

    public function getColor(): string
    {
        return static::COLOR;
    }

    public function getWeight(): float
    {
        return static::WEIGHT;
    }

    public function getName(): string
    {
        return static::NAME;
    }
}
