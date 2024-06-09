<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO\Input;

final readonly class ConfigDTO
{
    public function __construct(
        public array $thresholds,
        public array $exclude,
        public array $output,
    ) {
    }
}
