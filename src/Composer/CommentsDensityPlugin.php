<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

use function chmod;
use function copy;
use function file_exists;
use function file_put_contents;

final class CommentsDensityPlugin implements PluginInterface, EventSubscriberInterface
{
    private const CONFIG = <<<PHP
<?php

return [
    'directories' => [
        'src',
    ],
    'exclude' => [
        'src/DTO',
    ],
    'thresholds' => [
        'docBlock' => 90,
        'regular' => 5,
        'todo' => 5,
        'fixme' => 5,
        'missingDocBlock' => 10,
        'Com/LoC' => 0.1,
        'CDS' => 0.1,
    ],
    'only' => [
        'missingDocblock'
    ],
    'output' => [
        'type' => 'console', // "console" or 'html'
        'file' => 'output.html', // file path for HTML output
    ],
    'missingDocblock' => [
        'class' => true,
        'interface' => true,
        'trait' => true,
        'enum' => true,
        'property' => true,
        'constant' => true,
        'function' => true,
    ]
];

PHP;
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

        self::promptForPreCommitHook($interface);
        self::promptForConfigFile($interface);
    }

    private static function promptForConfigFile(IOInterface $interface): void
    {
        $interface->write('Run configuration file setup');
        $shouldCreateConfig = $interface->askConfirmation('Do you want to create a default configuration file? [y/N] ');

        if ($shouldCreateConfig) {
            $interface->write('Creating default configuration file...');

            file_put_contents('comments_density.php', self::CONFIG);

            $interface->write('Default configuration file created.');
            return;
        }

        $interface->write('Configuration file setup skipped.');
    }

    public static function promptForPreCommitHook(IOInterface $ioHelper): void
    {
        $ioHelper->write('Run pre-commit installation');
        $shouldInstallHook = $ioHelper->askConfirmation('Do you want to install the pre-commit hook? [y/N] ');

        if ($shouldInstallHook) {
            $ioHelper->write('Installing pre-commit hook...');

            $source = __DIR__ . '/../../pre-commit.sh';
            $destination = '.git/hooks/pre-commit';

            if (!file_exists($source)) {
                $ioHelper->writeError("Error: Source file $source does not exist.");
                return;
            }

            copy($source, $destination);
            chmod($destination, 0755);

            $ioHelper->write('Pre-commit hook installed.');
            return;
        }

        $ioHelper->write('Pre-commit hook installation skipped.');
    }
}
