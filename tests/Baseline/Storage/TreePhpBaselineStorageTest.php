<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Baseline\Storage;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SavinMikhail\CommentsDensity\Baseline\Storage\TreePhpBaselineStorage;
use SavinMikhail\CommentsDensity\DTO\Output\CommentDTO;

use function ltrim;

use const DIRECTORY_SEPARATOR;

final class TreePhpBaselineStorageTest extends TestCase
{
    private string $path;
    private TreePhpBaselineStorage $storage;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/baseline_tree.php';
        $this->storage = new TreePhpBaselineStorage();
        $this->storage->init($this->path);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }

    public function testInitCreatesFileIfNotExists(): void
    {
        unlink($this->path);
        $this->storage->init($this->path);
        $this->assertFileExists($this->path);
    }

    public function testSetComments(): void
    {
        $comments = [
            new CommentDTO('regular', 'red', '/path/to/file1.php', 10, 'Test comment 1'),
            new CommentDTO('regular', 'red', '/path/to/file2.php', 20, 'Test comment 2'),
        ];

        $this->storage->setComments($comments);

        $expectedData = [
            'path' => [
                'to' => [
                    'file1.php' => [
                        10 => ['comment' => 'Test comment 1', 'type' => 'regular']
                    ],
                    'file2.php' => [
                        20 => ['comment' => 'Test comment 2', 'type' => 'regular']
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedData, include $this->path);
    }

    public function testFilterComments(): void
    {
        $existingComments = [
            new CommentDTO(
                'regular',
                'red',
                '/path/to/file1.php',
                10,
                'Test comment 1'
            ),
        ];
        $this->storage->setComments($existingComments);

        $comments = [
            ['file' => '/path/to/file1.php', 'line' => 10, 'content' => 'Test comment 1', 'commentType' => 'regular'],
            ['file' => '/path/to/file2.php', 'line' => 20, 'content' => 'Test comment 2', 'commentType' => 'regular'],
        ];

        $filteredComments = $this->storage->filterComments($comments);

        $expectedFilteredComments = [
            ['file' => '/path/to/file2.php', 'line' => 20, 'content' => 'Test comment 2', 'commentType' => 'regular'],
        ];

        $this->assertEquals($expectedFilteredComments, $filteredComments);
    }

    public function testAddCommentToTree(): void
    {
        $comment = new CommentDTO('regular', 'Test comment', '/path/to/file.php', 10, 'test comment');
        $pathParts = explode(DIRECTORY_SEPARATOR, ltrim($comment->file, DIRECTORY_SEPARATOR));
        $tree = [];

        $reflection = new ReflectionClass($this->storage);
        $method = $reflection->getMethod('addCommentToTree');
        $method->setAccessible(true);
        $method->invokeArgs($this->storage, [&$tree, $pathParts, $comment]);

        $expectedTree = [
            'path' => [
                'to' => [
                    'file.php' => [
                        10 => ['comment' => 'test comment', 'type' => 'regular']
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedTree, $tree);
    }

    public function testCommentExistsInTree(): void
    {
        $tree = [
            'path' => [
                'to' => [
                    'file.php' => [
                        10 => ['comment' => 'Test comment', 'type' => 'regular']
                    ]
                ]
            ]
        ];

        $pathParts = explode(DIRECTORY_SEPARATOR, ltrim('/path/to/file.php', DIRECTORY_SEPARATOR));
        $line = 10;

        $reflection = new ReflectionClass($this->storage);
        $method = $reflection->getMethod('commentExistsInTree');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->storage, [$tree, $pathParts, $line]);

        $this->assertTrue($result);
    }
}
