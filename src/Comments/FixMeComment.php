<?php

namespace SavinMikhail\CommentsDensity\Comments;

final class FixMeComment extends Comment
{
    public const PATTERN = '/(?:\/\/|#|\/\*|\*|<!--).*?\bfixme\b.*/i';
    public const COLOR = 'yellow';
    public const WEIGHT = -0.3;
    public const ATTITUDE = 'unwanted';
    public const NAME = 'fixme';

    protected function isWithinThreshold(int $count, array $thresholds): bool
    {
        return $count <= $thresholds[static::NAME];
    }
}
