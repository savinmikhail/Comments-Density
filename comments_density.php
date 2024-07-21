<?php

return [
    'directories' => [
        'src', // Directories to be scanned for comments
    ],
    'exclude' => [
        'src/DTO', // Directories to be ignored during scanning
    ],
    'thresholds' => [
        // Limit occurrences of each comment type
        'docBlock' => 90, 
        'regular' => 5,
        'todo' => 5,
        'fixme' => 5,
        'missingDocBlock' => 10,
        // Additional metrics thresholds
        'Com/LoC' => 0.1, // Comments per Lines of Code
        'CDS' => 0.1, // Comment Density Score
    ],
    'only' => [
        'missingDocblock', // Only this type will be analyzed; set to empty array for full statistics
    ],
    'output' => [
        'type' => 'console', // Supported values: 'console', 'html'
        'file' => 'output.html', // File path for HTML output (only used if type is 'html')
    ],
    'missingDocblock' => [
        'class' => true, // Check for missing docblocks in classes
        'interface' => true, // Check for missing docblocks in interfaces
        'trait' => true, // Check for missing docblocks in traits
        'enum' => true, // Check for missing docblocks in enums
        'property' => true, // Check for missing docblocks in properties
        'constant' => true, // Check for missing docblocks in constants
        'function' => true, // Check for missing docblocks in functions
         // If false, only methods where @throws tag or generic can be applied will be checked
        'requireForAllMethods' => true,
    ],
    'use_baseline' => true, // Filter collected comments against the baseline stored in baseline.php
];
