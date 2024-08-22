<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO\Input;

final readonly class OutputDTO
{
    public function __construct(
        public string $type,
        public string $file,
    ) {}
}
