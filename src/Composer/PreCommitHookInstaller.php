<?php

namespace SavinMikhail\CommentsDensity\Composer;

use Composer\Script\Event;

final readonly class PreCommitHookInstaller
{
    public static function promptForPreCommitHook(Event $event)
    {
        $io = $event->getIO();

        if ($io->askConfirmation('Do you want to install the pre-commit hook? [y/N] ', false)) {
            $io->write('Installing pre-commit hook...');
            copy('vendor/savinmikhail/comments-density/commit_density_pre-commit.sh', '.git/hooks/pre-commit');
            chmod('.git/hooks/pre-commit', 0755);
            $io->write('Pre-commit hook installed.');
        } else {
            $io->write('Pre-commit hook installation skipped.');
        }
    }
}
