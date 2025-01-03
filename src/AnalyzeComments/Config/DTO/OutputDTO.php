<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO;

abstract readonly class OutputDTO
{
    protected function __construct(
        /** Supported values: 'console', 'html' */
        public string $type,
    ) {}

    abstract public static function create(): static;
}
