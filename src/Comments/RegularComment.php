<?php

namespace SavinMikhail\CommentsDensity\Comments;

final class RegularComment extends Comment
{
    public const PATTERN = '/(#(?!.*\b(?:todo|fixme)\b:?).*?$)|(\/\/(?!.*\b(?:todo|fixme)\b:?).*?$)|\/\*(?!\*)(?!.*\b(?:todo|fixme)\b:?).*?\*\//ms';
    public const COLOR = 'red';
    public const WEIGHT = -1;
    public const COMPARISON_TYPE = '<=';
    public const NAME = 'regular';
}
