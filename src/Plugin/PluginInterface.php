<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Plugin;

use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\Report;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;

/**
 * @see FileEditor to remove/update comments
 */
interface PluginInterface
{
    public function handle(Report $report, Config $config): void;
}
