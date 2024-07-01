<?php

namespace SavinMikhail\CommentsDensity\DTO\Output;

final readonly class CommentStatisticsDTO
{
    public function __construct(
        public string $typeColor,
        public string $type,
        public string $lines,
        public string $color,
        public int $count,
    ) {
    }
}
