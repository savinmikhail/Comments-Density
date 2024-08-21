<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Comments;

final class LicenseComment extends Comment
{
    public const PATTERN = '/\/\*\*.*?\b(?:license|copyright|permission)\b.*?\*\//is';
    public const COLOR = 'white';
    public const WEIGHT = 0;
    public const COMPARISON_TYPE = '>=';
    public const NAME = 'license';
}
