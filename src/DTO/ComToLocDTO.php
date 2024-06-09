<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO;

final readonly class ComToLocDTO {
    public function __construct(
        public float $comToLoc,
        public string $color,
    ) {
    }
}