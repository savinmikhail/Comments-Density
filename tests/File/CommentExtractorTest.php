<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\File;

use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\CommentTypeFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConsoleOutputDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\File\CommentFinder;

final class CommentExtractorTest extends TestCase
{
    private CommentFinder $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new CommentFinder(
            new CommentTypeFactory(),
            new Config(
                output: ConsoleOutputDTO::create(),
                directories: [],
            ),
        );
    }

    public function test(): void
    {
        $content = <<<'PHP'
            <?php
            /**
             * @see https://docs.npmjs.com/cli/v10/configuring-npm/package-json#repository
             * @see https://github.com/npm/registry/blob/main/docs/responses/package-metadata.md
             */
            #[AutoconfigureTag('artifact_metadata')]
            final class ArtifactMetadata extends AbstractArtifactMetadata {}
            PHP;
        $comments = ($this->analyzer)($content, 'test.php');
        self::assertCount(1, $comments);
    }
}
