<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO\Input;

final readonly class ConfigDTO
{
    /**
     * @param array<string, float> $thresholds
     * @param string[] $exclude
     * @param OutputDTO $output
     * @param string[] $directories
     * @param string[] $only
     * @param MissingDocblockConfigDTO $docblockConfigDTO
     * @param bool $useBaseline
     */
    public function __construct(
        public array $thresholds,
        public array $exclude,
        public OutputDTO $output,
        public array $directories,
        public array $only,
        public MissingDocblockConfigDTO $docblockConfigDTO,
        public bool $useBaseline,
    ) {
    }
}
