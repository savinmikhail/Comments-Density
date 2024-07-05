<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO\Input;

final readonly class MissingDocblockConfigDTO
{
    public function __construct(
        public bool $class,
        public bool $interface,
        public bool $trait,
        public bool $enum,
        public bool $function,
        public bool $property,
        public bool $constant,
    ) {
    }
}