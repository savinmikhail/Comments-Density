<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Comments;

final class RegularComment extends Comment
{
    // phpcs:ignore Generic.Files.LineLength.TooLong
    public const PATTERN =
        '/(#(?!.*\b(?:todo|fixme)\b:?).*?$)|(\/\/(?!.*\b(?:todo|fixme)\b:?).*?$)|\/\*(?!\*)(?!.*\b(?:todo|fixme)\b:?).*?\*\//ms';
    public const COLOR = 'red';
    public const WEIGHT = -1;
    public const COMPARISON_TYPE = '<=';
    public const NAME = 'regular';
}
