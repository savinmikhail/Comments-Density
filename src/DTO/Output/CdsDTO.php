<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO\Output;

final readonly class CdsDTO
{
    public function __construct(
        public float $cds,
        public string $color,
    ) {
    }
}
