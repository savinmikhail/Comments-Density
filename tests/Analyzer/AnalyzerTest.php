<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Analyzer;

use Generator;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\Analyzer\Analyzer;
use SavinMikhail\CommentsDensity\Baseline\Storage\BaselineStorageInterface;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\Comments\CommentTypeInterface;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\DTO\Output\OutputDTO;
use SavinMikhail\CommentsDensity\DTO\Output\PerformanceMetricsDTO;
use SavinMikhail\CommentsDensity\Metrics\MetricsFacade;
use SavinMikhail\CommentsDensity\MissingDocblock\MissingDocBlockAnalyzer;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;
use ReflectionClass;

final class AnalyzerTest extends TestCase
{
    private Analyzer $analyzer;
    private MockObject $configDTO;
    private MockObject $commentFactory;
    private MockObject $missingDocBlock;
    private MockObject $metrics;
    private MockObject $output;
    private MockObject $docBlockAnalyzer;
    private MockObject $baselineStorage;

    protected function setUp(): void
    {
        $this->configDTO = $this->createMock(ConfigDTO::class);
        $this->commentFactory = $this->createMock(CommentFactory::class);
        $this->missingDocBlock = $this->createMock(MissingDocBlockAnalyzer::class);
        $this->metrics = $this->createMock(MetricsFacade::class);
        $this->output = $this->createMock(OutputInterface::class);
        $this->docBlockAnalyzer = $this->createMock(MissingDocBlockAnalyzer::class);
        $this->baselineStorage = $this->createMock(BaselineStorageInterface::class);

        $this->analyzer = $this->getMockBuilder(Analyzer::class)
            ->setConstructorArgs([
                $this->configDTO,
                $this->commentFactory,
                $this->missingDocBlock,
                $this->metrics,
                $this->output,
                $this->docBlockAnalyzer,
                $this->baselineStorage
            ])
            ->onlyMethods(['isInWhitelist'])
            ->getMock();

        // Setup virtual file system
        vfsStream::setup('root', null, [
            'file1.php' => "<?php\n// Test comment\n/** Test doc comment */\n"
        ]);
    }

    public function testAnalyzeWithFiles(): void
    {
        $this->markTestIncomplete();
        $files = $this->createMockFiles();
        $this->metrics->expects($this->once())->method('startPerformanceMonitoring');
        $this->metrics->expects($this->once())->method('stopPerformanceMonitoring');
        $this->metrics->expects($this->once())->method('getPerformanceMetrics')
            ->willReturn($this->createMock(PerformanceMetricsDTO::class));

        $comments = [
            new CommentDTO('regular', 'red', vfsStream::url('root/file1.php'), 10, 'Test comment 1')
        ];

        $this->baselineStorage->expects($this->once())->method('filterComments')->willReturn($comments);

        $outputDTO = $this->analyzer->analyze($files);

        $this->assertInstanceOf(OutputDTO::class, $outputDTO);
        $this->assertEquals(1, $outputDTO->filesAnalyzed);
    }

    public function testAnalyzeFile(): void
    {
        $filename = vfsStream::url('root/file1.php');
        $this->output->expects($this->once())->method('writeln')->with("<info>Analyzing $filename</info>");

        $reflection = new ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('analyzeFile');
        $method->setAccessible(true);

        $commentsAndLines = $method->invokeArgs($this->analyzer, [$filename]);

        $this->assertIsArray($commentsAndLines);
        $this->assertArrayHasKey('comments', $commentsAndLines);
        $this->assertArrayHasKey('linesOfCode', $commentsAndLines);
    }

    public function testGetCommentsFromFile(): void
    {
        $tokens = [
            [T_COMMENT, '// Test comment', 1],
            [T_DOC_COMMENT, '/** Test doc comment */', 2]
        ];
        $filename = vfsStream::url('root/file1.php');

        $commentTypeMock = $this->createMock(CommentTypeInterface::class);
        $commentTypeMock->method('getName')->willReturn('regular');
        $this->commentFactory->expects($this->exactly(2))->method('classifyComment')
            ->willReturn($commentTypeMock);

        $reflection = new ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('getCommentsFromFile');
        $method->setAccessible(true);

        $comments = $method->invokeArgs($this->analyzer, [$tokens, $filename]);

        $this->assertIsArray($comments);
        $this->assertCount(2, $comments);
        $this->assertEquals([
            'content' => '// Test comment',
            'type' => $commentTypeMock,
            'line' => 1,
            'file' => $filename
        ], $comments[0]);
    }

    public function testCountTotalLines(): void
    {
        $filename = vfsStream::url('root/file1.php');

        $reflection = new ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('countTotalLines');
        $method->setAccessible(true);

        $linesOfCode = $method->invokeArgs($this->analyzer, [$filename]);

        $this->assertEquals(3, $linesOfCode);
    }

    public function testIsPhpFile(): void
    {
        $file = new SplFileInfo(__FILE__); // Use the current test file to ensure it's a PHP file

        $reflection = new ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('isPhpFile');
        $method->setAccessible(true);

        $this->assertTrue($method->invokeArgs($this->analyzer, [$file]));
    }

    public function testCountCommentOccurrences(): void
    {
        $comments = [
            [
                'content' => '// Test comment',
                'type' => 'regular',
                'line' => 1,
                'file' => '/path/to/file1.php'
            ],
            [
                'content' => '/** Test doc comment */',
                'type' => 'docBlock',
                'line' => 2,
                'file' => '/path/to/file1.php'
            ]
        ];

        $reflection = new ReflectionClass($this->analyzer);
        $method = $reflection->getMethod('countCommentOccurrences');
        $method->setAccessible(true);

        $occurrences = $method->invokeArgs($this->analyzer, [$comments]);

        $this->assertIsArray($occurrences);
        $this->assertArrayHasKey('regular', $occurrences);
        $this->assertArrayHasKey('docBlock', $occurrences);
    }

    private function createMockFiles(): Generator
    {
        yield new SplFileInfo(vfsStream::url('root/file1.php'));
    }
}
