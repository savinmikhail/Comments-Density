<?php

declare(strict_types=1);

use SavinMikhail\CommentsDensity\Config\DTO\ConfigDTO;
use SavinMikhail\CommentsDensity\Config\DTO\ConsoleOutputDTO;
use SavinMikhail\CommentsDensity\Config\DTO\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\Config\DTO\OutputDTO;

return new ConfigDTO(
    thresholds: [
        'docBlock' => 90,
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
    docblockConfigDTO: new MissingDocblockConfigDTO(),
    disable: [
        'missingDocBlock',
    ]
);
