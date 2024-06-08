<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

final class CommentsDensityPlugin implements PluginInterface, EventSubscriberInterface
{
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
            ScriptEvents::POST_INSTALL_CMD => 'promptForPreCommitHook',
            ScriptEvents::POST_UPDATE_CMD => 'promptForPreCommitHook',
        ];
    }

    public static function promptForPreCommitHook(Event $event): void
    {
        $ioHelper = $event->getIO();
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
