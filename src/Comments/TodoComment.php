<?php

namespace SavinMikhail\CommentsDensity\Comments;

final class TodoComment extends Comment
{
    public const PATTERN = '/(?:\/\/|#|\/\*|\*|<!--).*?\btodo\b.*/i';
    public const COLOR = 'yellow';
    public const WEIGHT = -0.3;
    public const ATTITUDE = 'unwanted';
    public const NAME = 'todo';

    protected function isWithinThreshold(int $count, array $thresholds): bool
    {
        return $count <= $thresholds[static::NAME];
    }
}
