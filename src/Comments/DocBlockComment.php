<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Comments;

final class DocBlockComment extends Comment
{
    public const PATTERN = '/\/\*\*(?!.*\b(?:license|copyright|permission)\b).+?\*\//is';
    public const COLOR = 'green';
    public const WEIGHT = 1;
    public const COMPARISON_TYPE = '>=';
    public const NAME = 'docBlock';
}
