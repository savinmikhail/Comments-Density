<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\ComposerPlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

use function file_put_contents;

final class CommentsDensityPlugin implements PluginInterface, EventSubscriberInterface
{
    private const CONFIG = <<<'PHP'
        <?php
        
        declare(strict_types=1);
        
        use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;
        use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConsoleOutputDTO;
        use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\MissingDocblockConfigDTO;
        
        return new Config(
            thresholds: [
                'docBlock' => 1000,
                'regular' => 5,
                'todo' => 1,
                'fixme' => 5,
                'missingDocBlock' => 1,
                'Com/LoC' => 0.1,
                'CDS' => 0.1,
            ],
            exclude: [
                'src/DTO',
            ],
            output: ConsoleOutputDTO::create(),
            directories: [
                'src',
            ],
            docblockConfigDTO: new MissingDocblockConfigDTO(class: true),
            disable: []
        );

        PHP;

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'promptForSetup',
            ScriptEvents::POST_UPDATE_CMD => 'promptForSetup',
        ];
    }

    public static function promptForSetup(Event $event): void
    {
        $interface = $event->getIO();

        self::promptForConfigFile($interface);
    }

    private static function promptForConfigFile(IOInterface $interface): void
    {
        $interface->write('Run configuration file setup');
        $shouldCreateConfig = $interface
            ->askConfirmation('Do you want to create a default configuration file? [y/n]');

        if (!$shouldCreateConfig) {
            $interface->write('Configuration file setup skipped.');

            return;
        }

        $res = file_put_contents('../../comments_density.php', self::CONFIG);

        if ($res === false) {
            $interface->error('Configuration file setup failed.');
        }

        $interface->info('Default configuration file created.');
    }

    /**
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        // No activation logic needed
    }

    /**
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // No deactivation logic needed
    }

    /**
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // No uninstallation logic needed
    }
}
