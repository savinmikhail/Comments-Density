<?php

return [
    'directories' => [
        'src',
    ],
    'exclude' => [
        'src/DTO',
    ],
    'thresholds' => [
        'docBlock' => 90,
        'regular' => 5,
        'todo' => 5,
        'fixme' => 5,
        'missingDocBlock' => 10,
        'Com/LoC' => 0.1,
        'CDS' => 0.1,
    ],
    'only' => [
        'missingDocblock'
    ],
    'output' => [
        'type' => 'console', // "console" or 'html'
        'file' => 'output.html', // file path for HTML output
    ],
];
