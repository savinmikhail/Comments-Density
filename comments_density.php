<?php

declare(strict_types=1);

use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\FixMeComment;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\RegularComment;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\TodoComment;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;

return new Config(
    directories: [
        'src',
    ],
    thresholds: [
        RegularComment::NAME => 0,
        TodoComment::NAME => 0,
        FixMeComment::NAME => 0,
    ],
);
