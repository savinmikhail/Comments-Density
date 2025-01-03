<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO;

final readonly class MissingDocblockConfigDTO
{
    public function __construct(
        public bool $class = false,
        public bool $interface = false,
        public bool $trait = false,
        public bool $enum = false,
        public bool $function = false,
        public bool $property = false,
        public bool $constant = false,
    ) {}
}
