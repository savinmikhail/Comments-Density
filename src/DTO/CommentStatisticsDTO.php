<?php

namespace SavinMikhail\CommentsDensity\DTO;

final readonly class CommentStatisticsDTO
{
    public function __construct(
        public string $typeColor,
        public string $type,
        public int $count,
        public string $color,
    ) {
    }
}