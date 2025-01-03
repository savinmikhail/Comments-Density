<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Comments;

interface CommentTypeInterface
{
    public function getColor(): string;

    /** @param array<string, float> $thresholds */
    public function getStatColor(int $count, array $thresholds): string;

    public function getWeight(): float;

    public function getName(): string;

    public function matchesPattern(string $token): bool;

    public function hasExceededThreshold(): bool;
}
