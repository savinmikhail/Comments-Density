<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO;

use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\DocBlockComment;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\FixMeComment;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\LicenseComment;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\MissingDocBlock;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\RegularComment;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\TodoComment;

final readonly class Config
{
    /**
     * @param array<string, float> $thresholds
     * @param string[] $exclude
     * @param string[] $directories
     * @param string[] $disable
     */
    public function __construct(
        public OutputDTO $output,
        /** Directories to be scanned for comments */
        public array $directories,
        public MissingDocblockConfigDTO $docblockConfigDTO = new MissingDocblockConfigDTO(),
        /** Limit occurrences of each comment type */
        public array $thresholds = [],
        /** Directories to be ignored during scanning */
        public array $exclude = [],
        /** Filter collected comments against the baseline stored in baseline.php */
        public bool $useBaseline = true,
        public string $cacheDir = 'var/cache/comments-density',
        /** Disable certain types; set to empty array for full statistics */
        public array $disable = [],
    ) {}

    /**
     * @return non-empty-string[]
     */
    public function getAllowedTypes(): array
    {
        $types = [
            DocBlockComment::NAME,
            RegularComment::NAME,
            LicenseComment::NAME,
            TodoComment::NAME,
            FixMeComment::NAME,
            MissingDocBlock::NAME,
        ];

        return array_diff($types, $this->disable);
    }
}
