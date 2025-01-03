<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Formatter;

use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConfigDTO;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class FormatterFactory
{
    public function createReporter(OutputInterface $output, ConfigDTO $configDto): FormatterInterface
    {
        if ($configDto->output->type === 'html') {
            return new HtmlFormatter($configDto->output->file);
        }

        return new ConsoleFormatter($output);
    }
}
