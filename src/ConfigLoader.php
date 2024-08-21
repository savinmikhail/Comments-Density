<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use Exception;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Input\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Input\OutputDTO;
use SavinMikhail\CommentsDensity\Exception\CommentsDensityException;

use function array_map;
use function defined;
use function dirname;
use function file_exists;
use function is_dir;

use const DIRECTORY_SEPARATOR;

final readonly class ConfigLoader
{
    private const CONFIG_FILE = 'comments_density.php';
    private const DIR_LEVEL = COMMENTS_DENSITY_ENVIRONMENT === 'dev' ? 1 : 4;

    public function __construct()
    {
        if (!defined('COMMENTS_DENSITY_ENVIRONMENT')) {
            throw new Exception('Environment is not set');
        }
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
            $this->getMissingDocblockConfig($config),
            $config['use_baseline'],
            $this->getProjectRoot() . '/var/comments_density_cache',
        );
    }

    /**
     * @return array<mixed><mixed>
     *
     * @throws CommentsDensityException
     */
    private function parseConfigFile(string $configFile): array
    {
        if (! file_exists($configFile)) {
            throw new CommentsDensityException('Config file does not exists! Looking for ' . $configFile);
        }

        return require_once $configFile;
    }

    /**
     * @param array<mixed> $config
     */
    private function getOutput(array $config): OutputDTO
    {
        $type = $config['output']['type'] ?? 'console';
        $file = $config['output']['file'] ?? 'output.html';
        $file = $this->getProjectRoot() . DIRECTORY_SEPARATOR . $file;

        return new OutputDTO($type, $file);
    }

    /**
     * @param array<mixed> $config
     * @return array<mixed>
     */
    private function getOnly(array $config): array
    {
        return $config['only'] ?? [];
    }

    /**
     * @param array<mixed> $config
     * @return array<mixed>
     */
    private function getThresholds(array $config): array
    {
        return $config['thresholds'] ?? [];
    }

    /**
     * @return mixed[]
     * @throws CommentsDensityException
     */
    private function getConfig(): array
    {
        $configFile = $this->getProjectRoot() . DIRECTORY_SEPARATOR . self::CONFIG_FILE;

        return $this->parseConfigFile($configFile);
    }

    /**
     * @param array<mixed> $config
     * @return array<mixed>
     */
    private function getExcludes(array $config): array
    {
        return array_map(
            fn($dir) => $this->getProjectRoot() . '/' . $dir,
            $config['exclude'],
        );
    }

    /**
     * @param array<mixed> $config
     * @return array<mixed>
     * @throws CommentsDensityException
     */
    private function getDirectories(array $config): array
    {
        $directories = array_map(
            fn($dir) => $this->getProjectRoot() . '/' . $dir,
            $config['directories'],
        );
        foreach ($directories as $dir) {
            if (! is_dir($dir)) {
                throw new CommentsDensityException($dir . ' directory does not exist');
            }
        }

        return $directories;
    }

    private function getProjectRoot(): string
    {
        return dirname(__DIR__, self::DIR_LEVEL);
    }

    /**
     * @param array<mixed> $config
     */
    private function getMissingDocblockConfig(array $config): MissingDocblockConfigDTO
    {
        return new MissingDocblockConfigDTO(
            class: $config['missingDocblock']['class'],
            interface: $config['missingDocblock']['interface'],
            trait: $config['missingDocblock']['trait'],
            enum: $config['missingDocblock']['enum'],
            function: $config['missingDocblock']['function'],
            property: $config['missingDocblock']['property'],
            constant: $config['missingDocblock']['constant'],
            requireForAllMethods: $config['missingDocblock']['requireForAllMethods'],
        );
    }
}
