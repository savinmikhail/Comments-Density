<?php

namespace SavinMikhail\CommentsDensity\Comments;

final class TodoComment extends Comment
{
    public const PATTERN = '/(?:\/\/|#|\/\*|\*|<!--).*?\btodo\b.*/i';
    public const COLOR = 'yellow';
    public const WEIGHT = -0.3;
    public const COMPARISON_TYPE = '<=';
    public const NAME = 'todo';
}
