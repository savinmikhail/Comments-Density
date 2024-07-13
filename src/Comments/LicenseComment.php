<?php

namespace SavinMikhail\CommentsDensity\Comments;

final class LicenseComment extends Comment
{
    public const PATTERN = '/\/\*\*.*?\b(?:license|copyright|permission)\b.*?\*\//is';
    public const COLOR = 'white';
    public const WEIGHT = 0;
    public const ATTITUDE = 'neutral';
    public const NAME = 'license';

    protected function isWithinThreshold(int $count, array $thresholds): bool
    {
        return $count >= $thresholds[static::NAME];
    }
}
