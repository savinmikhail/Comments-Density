<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Config;

use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConfigDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Exception\CommentsDensityException;
use function defined;
use function dirname;
use function file_exists;
use const DIRECTORY_SEPARATOR;

final readonly class ConfigLoader
{
    private const CONFIG_FILE = 'comments_density.php';
    private const DIR_LEVEL = COMMENTS_DENSITY_ENVIRONMENT === 'dev' ? 3 : 6;

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
    public function getConfigDto(): ConfigDTO
    {
        $configFile = $this->getProjectRoot() . DIRECTORY_SEPARATOR . self::CONFIG_FILE;
        if (!file_exists($configFile)) {
            throw new CommentsDensityException('Config file does not exists! Looking for ' . $configFile);
        }
        $config = require $configFile;

        if (!$config instanceof ConfigDTO) {
            throw new CommentsDensityException('Config file must return an instance of ConfigDTO');
        }

        return $config;
    }

    public function getProjectRoot(): string
    {
        return dirname(__DIR__, self::DIR_LEVEL);
    }
}
