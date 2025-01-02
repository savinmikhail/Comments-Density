<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Config\DTO;

final readonly class OutputDTO
{
    public function __construct(
        /** Supported values: 'console', 'html' */
        public string $type,
        /** File path for HTML output (only used if type is 'html') */
        public string $file,
    ) {}
}
