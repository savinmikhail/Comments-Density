<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use Exception;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Input\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\Exception\CommentsDensityException;

use function array_map;
use function dirname;
use function file_exists;
use function is_dir;

use const DIRECTORY_SEPARATOR;

final readonly class ConfigLoader
{
    protected const CONFIG_FILE = 'comments_density.php';
    protected const DIR_LEVEL = 4;

    /**
     * @throws Exception
     */
    protected function parseConfigFile(string $configFile): array
    {
        if (! file_exists($configFile)) {
            throw new CommentsDensityException('Config file does not exists! Looking for ' . $configFile);
        }
        return require_once $configFile;
    }

    protected function getOutput(array $config): array
    {
        $outputConfig = $config['output'];
        $outputConfig['file'] = $this->getProjectRoot() . DIRECTORY_SEPARATOR . $outputConfig['file'];
        return $outputConfig;
    }

    protected function getOnly(array $config): array
    {
        return $config['only'] ?? [];
    }

    protected function getThresholds(array $config): array
    {
        return $config['thresholds'] ?? [];
    }

    /**
     * @throws CommentsDensityException
     */
    public function getConfigDto(): ConfigDTO
    {
        $config = $this->getConfig();

        return new ConfigDTO(
            $this->getThresholds($config),
            $this->getExcludes($config),
            $this->getOutput($config),
            $this->getDirectories($config),
            $this->getOnly($config),
            $this->getMissingDocblockConfig($config)
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

    /**
     * @throws CommentsDensityException
     */
    protected function getDirectories(array $config): array
    {
        $directories =  array_map(
            fn($dir) => $this->getProjectRoot() . '/' . $dir,
            $config['directories']
        );
        foreach ($directories as $dir) {
            if (! is_dir($dir)) {
                throw new CommentsDensityException($dir . ' directory does not exist');
            }
        }
        return $directories;
    }

    protected function getProjectRoot(): string
    {
        return dirname(__DIR__, self::DIR_LEVEL);
    }

    protected function getMissingDocblockConfig(array $config): MissingDocblockConfigDTO
    {
        return new MissingDocblockConfigDTO(
            class: $config['missingDocblock']['class'],
            interface: $config['missingDocblock']['interface'],
            trait: $config['missingDocblock']['trait'],
            enum: $config['missingDocblock']['enum'],
            function: $config['missingDocblock']['function'],
            property: $config['missingDocblock']['property'],
            constant: $config['missingDocblock']['constant'],
            requireForAllMethods: $config['missingDocblock']['requireForAllMethods']
        );
    }
}
