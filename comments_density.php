<?php

declare(strict_types=1);

use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;

return new Config(
    directories: [
        'src',
    ],
    thresholds: [
        'regular' => 0,
        'todo' => 0,
        'fixme' => 0,
    ],
);
