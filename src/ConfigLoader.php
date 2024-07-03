<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;

use function array_map;
use function dirname;

use const DIRECTORY_SEPARATOR;

final readonly class ConfigLoader
{
    protected const CONFIG_FILE = 'comments_density.php';
    protected const DIR_LEVEL = 4;

    protected function parseConfigFile(string $configFile): array
    {
        return require_once $configFile;
    }

    public function getConfigDto(): ConfigDTO
    {
        $config = $this->getConfig();
        $exclude = $this->getExcludes($config);
        $thresholds = $config['thresholds'];
        $outputConfig = $config['output'];
        $outputConfig['file'] = $this->getProjectRoot() . DIRECTORY_SEPARATOR . $outputConfig['file'];
        $directories = $this->getDirectories($config);
        $only = $config['only'] ?? [];

        return new ConfigDTO(
            $thresholds,
            $exclude,
            $outputConfig,
            $directories,
            $only
        );
    }

    protected function getConfig(): array
    {
        $configFile = $this->getProjectRoot() . DIRECTORY_SEPARATOR . self::CONFIG_FILE;
        return $this->parseConfigFile($configFile);
    }

    protected function getExcludes(array $config): array
    {
        return array_map(
            fn($dir) => $this->getProjectRoot() . '/' . $dir,
            $config['exclude']
        );
    }

    protected function getDirectories(array $config): array
    {
        return array_map(
            fn($dir) => $this->getProjectRoot() . '/' . $dir,
            $config['directories']
        );
    }

    protected function getProjectRoot(): string
    {
        return dirname(__DIR__, self::DIR_LEVEL);
    }
}
