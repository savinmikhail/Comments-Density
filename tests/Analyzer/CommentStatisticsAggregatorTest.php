<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Analyzer;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\Analyzer\CommentStatisticsAggregator;
use SavinMikhail\CommentsDensity\Comments\Comment;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Input\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Input\OutputDTO as OutputConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\Exception\CommentsDensityException;
use SavinMikhail\CommentsDensity\MissingDocblock\MissingDocBlockAnalyzer;

final class CommentStatisticsAggregatorTest extends TestCase
{
    private ConfigDTO $configDTO;
    private MockObject $commentFactory;
    private MockObject $missingDocBlock;
    private CommentStatisticsAggregator $aggregator;

    protected function setUp(): void
    {
        $this->configDTO = new ConfigDTO(
            thresholds: [],
            exclude: ['/excluded/dir'],
            output: new OutputConfigDTO('console', 'output.html'),
            directories: [],
            only: [],
            docblockConfigDTO: $this->createMock(MissingDocblockConfigDTO::class),
            useBaseline: false,
            cacheDir: 'tmp',
        );

        $this->commentFactory = $this->createMock(CommentFactory::class);
        $this->missingDocBlock = $this->createMock(MissingDocBlockAnalyzer::class);
        $this->aggregator = new CommentStatisticsAggregator(
            $this->configDTO,
            $this->commentFactory,
            $this->missingDocBlock
        );
    }

    public function testCalculateCommentStatisticsReturnsExpectedResult()
    {
        $comments = [
            new CommentDTO(
                'missingDocblock',
                'red',
                'file.php',
                2,
                'Missing @throws tag'
            ),
            new CommentDTO(
                'missingDocblock',
                'red',
                'file.php',
                5,
                'Missing @throws tag'
            ),
            new CommentDTO(
                'regular',
                'red',
                'file.php',
                3,
                '//some comment',
            ),
        ];

        $this->missingDocBlock->method('getName')->willReturn('missingDocblock');
        $this->missingDocBlock->method('getColor')->willReturn('red');
        $this->missingDocBlock->method('getStatColor')->willReturn('red');

        $commentType = $this->createMock(Comment::class);
        $commentType->method('getColor')->willReturn('red');
        $commentType->method('getName')->willReturn('regular');
        $commentType->method('getStatColor')->willReturn('red');

        $this->commentFactory->method('getCommentType')->willReturnMap([
            ['regular', $commentType],
        ]);

        $expectedStatistics = [
            new CommentStatisticsDTO('red', 'missingDocblock', 2, 'red', 2),
            new CommentStatisticsDTO('red', 'regular', 1, 'red', 1),
        ];

        $result = $this->aggregator->calculateCommentStatistics($comments);

        $this->assertEquals($expectedStatistics, $result);
    }

    public function testCalculateCommentStatisticsWithMissingDocBlock()
    {
        $comments = [
            new CommentDTO(
                'missingDocblock',
                'red',
                'file.php',
                2,
                'Missing @throws tag'
            ),
        ];

        $this->missingDocBlock->method('getName')->willReturn('missingDocblock');
        $this->missingDocBlock->method('getColor')->willReturn('red');
        $this->missingDocBlock->method('getStatColor')->willReturn('statColorRed');

        $expectedStatistics = [
            new CommentStatisticsDTO('red', 'missingDocblock', 1, 'statColorRed', 1),
        ];

        $result = $this->aggregator->calculateCommentStatistics($comments);

        $this->assertEquals($expectedStatistics, $result);
    }

    public function testCalculateCommentStatisticsThrowsExceptionForUnknownType()
    {
        $comments = [
            new CommentDTO(
                'unknown',
                'red',
                'file.php',
                2,
                ';;some unparsed comment',
            ),
        ];

        $this->commentFactory->method('getCommentType')->willReturn(null);

        $this->expectException(CommentsDensityException::class);
        $this->expectExceptionMessage('Failed to classify comment of type unknown');

        $this->aggregator->calculateCommentStatistics($comments);
    }
}
