<?php

declare(strict_types=1);

use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConsoleOutputDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\MissingDocblockConfigDTO;

return new Config(
    output: ConsoleOutputDTO::create(),
    directories: [
        'src',
    ],
    thresholds: [
        'regular' => 0,
        'todo' => 0,
        'fixme' => 0,
    ],
);
