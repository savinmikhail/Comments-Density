<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Cache;

final readonly class Cache
{
    private string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function setCache(string $filePath, array $data): void
    {
        $cacheFile = $this->getCacheFilePath($filePath);
        $this->ensureDirectoryExists(dirname($cacheFile));

        $cacheContent = [
            'timestamp' => filemtime($filePath),
            'data' => $data,
        ];

        file_put_contents($cacheFile, "<?php return " . var_export($cacheContent, true) . ";");
    }

    public function getCache(string $filePath): ?array
    {
        $cacheFile = $this->getCacheFilePath($filePath);

        if (file_exists($cacheFile)) {
            $cachedData = include $cacheFile;

            if ($cachedData['timestamp'] === filemtime($filePath)) {
                return $cachedData['data'];
            }
        }

        return null;
    }

    private function getCacheFilePath(string $filePath): string
    {
        $relativePath = ltrim(str_replace(DIRECTORY_SEPARATOR, '_', $filePath), DIRECTORY_SEPARATOR);
        return $this->cacheDir . $relativePath . '.php';
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }
}
