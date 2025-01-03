<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Reporters;

use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConfigDTO;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ReporterFactory
{
    public function createReporter(OutputInterface $output, ConfigDTO $configDto): ReporterInterface
    {
        if ($configDto->output->type === 'html') {
            return new HtmlReporter($configDto->output->file);
        }

        return new ConsoleReporter($output);
    }
}
