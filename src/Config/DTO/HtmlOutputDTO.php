<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Config\DTO;

final readonly class HtmlOutputDTO extends OutputDTO
{
    private function __construct(
        /** File path for HTML output */
        public string $file = 'output.html',
    ) {
        parent::__construct('html');
    }

    public static function create(): static
    {
        return new self('output.html');
    }
}
