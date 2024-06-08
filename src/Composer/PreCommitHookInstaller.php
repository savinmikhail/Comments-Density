<?php

namespace SavinMikhail\CommentsDensity\Composer;

use Composer\Script\Event;

final readonly class PreCommitHookInstaller
{
    public static function promptForPreCommitHook(Event $event): void
    {
        $io = $event->getIO();
        $io->write('Run pre-commit installation');
        $shouldInstallHook = $io->askConfirmation('Do you want to install the pre-commit hook? [y/N] ', false);
        if ($shouldInstallHook) {
            $io->write('Installing pre-commit hook...');
            copy('vendor/savinmikhail/comments-density/commit_density_pre-commit.sh', '.git/hooks/pre-commit');
            chmod('.git/hooks/pre-commit', 0755);
            $io->write('Pre-commit hook installed.');
            return;
        }
        $io->write('Pre-commit hook installation skipped.');
    }
}
