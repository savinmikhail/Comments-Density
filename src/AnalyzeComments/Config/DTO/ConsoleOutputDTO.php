<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO;

final readonly class ConsoleOutputDTO extends OutputDTO
{
    private function __construct()
    {
        parent::__construct('console');
    }

    public static function create(): static
    {
        return new self();
    }
}
