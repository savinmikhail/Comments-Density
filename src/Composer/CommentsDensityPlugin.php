<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

final class CommentsDensityPlugin implements  PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        // No activation logic needed
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // No deactivation logic needed
    }

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
        $io = $event->getIO();
        $io->write('Run pre-commit installation');
        $shouldInstallHook = $io->askConfirmation('Do you want to install the pre-commit hook? [y/N] ', false);

        if ($shouldInstallHook) {
            $io->write('Installing pre-commit hook...');

            $source = __DIR__ . '/../../pre-commit.sh'; // Adjust the relative path if necessary
            $destination = '.git/hooks/pre-commit';

            if (!file_exists($source)) {
                $io->writeError("Error: Source file $source does not exist.");
                return;
            }

            copy($source, $destination);
            chmod($destination, 0755);

            $io->write('Pre-commit hook installed.');
            return;
        }

        $io->write('Pre-commit hook installation skipped.');
    }
}