<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Comments;

final class MissingDocBlock extends Comment
{
    public const NAME = 'missingDocblock';
    public const COLOR = 'red';
    public const PATTERN = '/.*/';
    public const WEIGHT = -1;
    public const COMPARISON_TYPE = '<=';
}
