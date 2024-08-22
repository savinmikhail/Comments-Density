<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO\Output;

final readonly class ComToLocDTO
{
    public function __construct(
        public float $comToLoc,
        public string $color,
    ) {}
}
