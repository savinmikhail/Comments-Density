<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Config\DTO;

final readonly class ConfigDTO
{
    /**
     * @param array<string, float> $thresholds
     * @param string[] $exclude
     * @param string[] $directories
     * @param string[] $only
     */
    public function __construct(
        /** Limit occurrences of each comment type */
        public array $thresholds,
        /** Directories to be ignored during scanning */
        public array $exclude,
        public OutputDTO $output,
        /** Directories to be scanned for comments */
        public array $directories,
        /** Only this type will be analyzed; set to empty array for full statistics */
        public array $only,
        public MissingDocblockConfigDTO $docblockConfigDTO,
        /** Filter collected comments against the baseline stored in baseline.php */
        public bool $useBaseline,
        public string $cacheDir,
    ) {}
}
