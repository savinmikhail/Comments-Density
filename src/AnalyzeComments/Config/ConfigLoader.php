<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Config;

use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;
use SavinMikhail\CommentsDensity\AnalyzeComments\Exception\CommentsDensityException;

use function defined;
use function dirname;
use function file_exists;

use const DIRECTORY_SEPARATOR;

final class ConfigLoader
{
    private const CONFIG_FILE = 'comments_density.php';

    private int $dirLevel = 3;

    /**
     * @throws CommentsDensityException
     */
    public function __construct()
    {
        if (!defined('COMMENTS_DENSITY_ENVIRONMENT')) {
            throw new CommentsDensityException('COMMENTS_DENSITY_ENVIRONMENT is not set');
        }
    }

    /**
     * @throws CommentsDensityException
     */
    public function getConfigDto(): Config
    {
        $config = require $this->getConfigPath();

        if (!$config instanceof Config) {
            throw new CommentsDensityException('Config file must return an instance of ConfigDTO');
        }

        return $config;
    }

    public function getProjectRoot(): string
    {
        if (COMMENTS_DENSITY_ENVIRONMENT === 'prod') {
            $this->dirLevel = 6; // if installed directly via composer
        }

        return dirname(__DIR__, $this->dirLevel);
    }

    /**
     * @throws CommentsDensityException
     */
    private function getConfigPath(): string
    {
        $configFile = $this->getProjectRoot() . DIRECTORY_SEPARATOR . self::CONFIG_FILE;
        if (file_exists($configFile)) {
            return $configFile;
        }
        $this->dirLevel = 8; // if installed via barmani/composer-bin-plugin
        $newConfigFileLocation = $this->getProjectRoot() . DIRECTORY_SEPARATOR . self::CONFIG_FILE;
        if (file_exists($newConfigFileLocation)) {
            return $configFile;
        }

        throw new CommentsDensityException(
            'Config file does not exists! Looking for ' . $configFile . ' and ' . $newConfigFileLocation,
        );
    }
}
