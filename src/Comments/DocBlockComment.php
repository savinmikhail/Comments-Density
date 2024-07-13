<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Comments;

final class DocBlockComment extends Comment
{
    public const PATTERN = '/\/\*\*(?!.*\b(?:license|copyright|permission)\b).+?\*\//is';
    public const COLOR = 'green';
    public const WEIGHT = 1;
    public const ATTITUDE = 'good';
    public const NAME = 'docBlock';

    protected function isWithinThreshold(int $count, array $thresholds): bool
    {
        return $count >= $thresholds[static::NAME];
    }
}
