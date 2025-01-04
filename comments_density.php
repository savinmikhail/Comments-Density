<?php

declare(strict_types=1);

use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConfigDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConsoleOutputDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\MissingDocblockConfigDTO;

return new ConfigDTO(
    thresholds: [
        'docBlock' => 1000,
        'regular' => 5,
        'todo' => 1,
        'fixme' => 5,
        'missingDocBlock' => 1,
        'Com/LoC' => 0.1,
        'CDS' => 0.1,
    ],
    exclude: [
        'src/DTO',
    ],
    output: ConsoleOutputDTO::create(),
    directories: [
        'src',
    ],
    docblockConfigDTO: new MissingDocblockConfigDTO(class: true),
    disable: []
);
