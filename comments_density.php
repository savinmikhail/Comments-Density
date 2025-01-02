<?php

declare(strict_types=1);

use SavinMikhail\CommentsDensity\Config\DTO\ConfigDTO;
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
    output: new OutputDTO(
        type: 'console',
        file: 'output.html'
    ),
    directories: [
        'src',
    ],
    only: [
        'todo'
    ],
    docblockConfigDTO: new MissingDocblockConfigDTO(
        class: false,
        interface: false,
        trait: false,
        enum: false,
        function: false,
        property: false,
        constant: false,
    ),
    useBaseline: true,
    cacheDir: 'var/cache/comments-density',
);
