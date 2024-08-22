<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO\Output;

final readonly class CommentStatisticsDTO
{
    public function __construct(
        public string $typeColor,
        public string $type,
        public int $lines,
        public string $color,
        public int $count,
    ) {}
}
