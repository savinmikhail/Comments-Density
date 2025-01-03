<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO;

final readonly class ConfigDTO
{
    /**
     * @param array<string, float> $thresholds
     * @param string[] $exclude
     * @param string[] $directories
     * @param string[] $disable
     */
    public function __construct(
        /** Limit occurrences of each comment type */
        public array $thresholds,
        /** Directories to be ignored during scanning */
        public array $exclude,
        public OutputDTO $output,
        /** Directories to be scanned for comments */
        public array $directories,
        public MissingDocblockConfigDTO $docblockConfigDTO,
        /** Filter collected comments against the baseline stored in baseline.php */
        public bool $useBaseline = true,
        public string $cacheDir = 'var/cache/comments-density',
        /** Disable certain types; set to empty array for full statistics */
        public array $disable = [],
    ) {}

    public function getAllowedTypes(): array
    {
        $types = [
            'docBlock',
            'regular',
            'license',
            'todo',
            'fixme',
            'missingDocBlock',
        ];

        return array_diff($types, $this->disable);
    }
}
