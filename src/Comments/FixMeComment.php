<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Comments;

final class FixMeComment extends Comment
{
    public const PATTERN = '/(?:\/\/|#|\/\*|\*|<!--).*?\bfixme\b.*/i';
    public const COLOR = 'yellow';
    public const WEIGHT = -0.3;
    public const COMPARISON_TYPE = '<=';
    public const NAME = 'fixme';
}
