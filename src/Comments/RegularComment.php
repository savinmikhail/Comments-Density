<?php

namespace SavinMikhail\CommentsDensity\Comments;

final class RegularComment extends Comment
{
    public const PATTERN = '/(#(?!.*\b(?:todo|fixme)\b:?).*?$)|(\/\/(?!.*\b(?:todo|fixme)\b:?).*?$)|\/\*(?!\*)(?!.*\b(?:todo|fixme)\b:?).*?\*\//ms';
    public const COLOR = 'red';
    public const WEIGHT = -1;
    public const ATTITUDE = 'bad';
    public const NAME = 'regular';

    protected function isWithinThreshold(int $count, array $thresholds): bool
    {
        return $count <= $thresholds[static::NAME];
    }
}
