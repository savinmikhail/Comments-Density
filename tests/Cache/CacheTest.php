<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Cache;

use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\Cache\Cache;

final class CacheTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = __DIR__ . '/../../var/cache_tests/';
        $this->clearCacheDir();
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $this->clearCacheDir();
    }

    public function testSetAndGetCache(): void
    {
        $cache = new Cache($this->cacheDir);

        $filePath = __DIR__ . '/testFile.php';
        file_put_contents($filePath, '<?php // test file');

        $commentDTO = new CommentDTO('type', 'color', $filePath, 1, 'content');
        $comments = [$commentDTO];

        $cache->setCache($filePath, $comments);
        $cachedData = $cache->getCache($filePath);

        $this->assertNotNull($cachedData);
        $this->assertEquals($comments, $cachedData);

        unlink($filePath);
    }

    public function testCacheInvalidationOnFileModification(): void
    {
        $cache = new Cache($this->cacheDir);

        $filePath = __DIR__ . '/testFile.php';
        file_put_contents($filePath, '<?php // test file');

        $commentDTO = new CommentDTO('type', 'color', $filePath, 1, 'content');
        $data = [$commentDTO];

        $cache->setCache($filePath, $data);
        $cachedData = $cache->getCache($filePath);
        $this->assertNotNull($cachedData);
        $this->assertEquals($data, $cachedData);

        // Modify the file to invalidate the cache
        sleep(1); // Ensure the file modification time changes
        file_put_contents($filePath, '<?php // modified content');

        $cachedData = $cache->getCache($filePath);
        $this->assertNull($cachedData);

        unlink($filePath);
    }

    private function clearCacheDir(): void
    {
        if (is_dir($this->cacheDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }
            rmdir($this->cacheDir);
        }
    }
}
