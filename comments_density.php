<?php

declare(strict_types=1);

use SavinMikhail\CommentsDensity\Config\DTO\ConfigDTO;
use SavinMikhail\CommentsDensity\Config\DTO\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\Config\DTO\OutputDTO;

return new ConfigDTO(
    thresholds: [
        'docBlock' => 90,
        'regular' => 5,
        'todo' => 5,
        'fixme' => 5,
        'missingDocBlock' => 10,
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
        'missingDocblock',
    ],
    docblockConfigDTO: new MissingDocblockConfigDTO(
        class: true,
        interface: true,
        trait: true,
        enum: true,
        function: true,
        property: true,
        constant: true,
    ),
    useBaseline: true,
    cacheDir: 'var/cache/comments-density',
);
