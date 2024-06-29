<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Reporters;

use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use Symfony\Component\Console\Output\OutputInterface;

final class ReporterFactory
{
    public function createReporter(OutputInterface $output, ConfigDTO $configDto): ReporterInterface
    {
        if (! empty($configDto->outputConfig)) {
            return new ConsoleReporter($output);
        }
        if ($configDto->output['type'] === 'html') {
            return new HtmlReporter(__DIR__ . '/../../../' . $configDto->output['file']);
        }
        return new ConsoleReporter($output);
    }
}
