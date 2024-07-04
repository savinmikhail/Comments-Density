<?php

declare(strict_types=1);

namespace Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Mockery;
use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\Composer\CommentsDensityPlugin;

final class CommentsDensityPluginTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testActivate(): void
    {
        $composer = Mockery::mock(Composer::class);
        $io = Mockery::mock(IOInterface::class);

        $plugin = new CommentsDensityPlugin();
        $plugin->activate($composer, $io);

        $this->assertTrue(true); // No activation logic to test
    }

    public function testDeactivate(): void
    {
        $composer = Mockery::mock(Composer::class);
        $io = Mockery::mock(IOInterface::class);

        $plugin = new CommentsDensityPlugin();
        $plugin->deactivate($composer, $io);

        $this->assertTrue(true); // No deactivation logic to test
    }

    public function testUninstall(): void
    {
        $composer = Mockery::mock(Composer::class);
        $io = Mockery::mock(IOInterface::class);

        $plugin = new CommentsDensityPlugin();
        $plugin->uninstall($composer, $io);

        $this->assertTrue(true); // No uninstallation logic to test
    }

    public function testGetSubscribedEvents(): void
    {
        $events = CommentsDensityPlugin::getSubscribedEvents();
        $expectedEvents = [
            ScriptEvents::POST_INSTALL_CMD => 'promptForSetup',
            ScriptEvents::POST_UPDATE_CMD => 'promptForSetup',
        ];

        $this->assertSame($expectedEvents, $events);
    }

    public function testPromptForSetup(): void
    {
        $io = Mockery::mock(IOInterface::class);
        $io->shouldReceive('write')->with('Run pre-commit installation')->once();
        $io->shouldReceive('askConfirmation')->with('Do you want to install the pre-commit hook? [y/N] ')->once()->andReturn(false);
        $io->shouldReceive('write')->with('Pre-commit hook installation skipped.')->once();
        $io->shouldReceive('write')->with('Run configuration file setup')->once();
        $io->shouldReceive('askConfirmation')->with('Do you want to create a default configuration file? [y/N] ')->once()->andReturn(false);
        $io->shouldReceive('write')->with('Configuration file setup skipped.')->once();

        $event = Mockery::mock(Event::class);
        $event->shouldReceive('getIO')->andReturn($io);

        CommentsDensityPlugin::promptForSetup($event);
        //mockery assertions aint being counted by coverage
        $this->assertTrue(true);
    }


    public function testPromptForConfigFile(): void
    {
        $io = Mockery::mock(IOInterface::class);
        $io->shouldReceive('write')->with('Run configuration file setup')->once();
        $io->shouldReceive('askConfirmation')->with('Do you want to create a default configuration file? [y/N] ')->andReturn(true);
        $io->shouldReceive('write')->with('Creating default configuration file...')->once();
        $io->shouldReceive('write')->with('Default configuration file created.')->once();

        $plugin = new CommentsDensityPlugin();

        $reflection = new \ReflectionClass($plugin);
        $method = $reflection->getMethod('promptForConfigFile');
        $method->setAccessible(true);
        $method->invoke($plugin, $io);

        $this->assertFileExists('comments_density.php');
    }

    public function testPromptForPreCommitHook(): void
    {
        $io = Mockery::mock(IOInterface::class);
        $io->shouldReceive('write')->with('Run pre-commit installation')->once();
        $io->shouldReceive('askConfirmation')->with('Do you want to install the pre-commit hook? [y/N] ')->andReturn(true);
        $io->shouldReceive('write')->with('Installing pre-commit hook...')->once();
        $io->shouldReceive('write')->with('Pre-commit hook installed.')->once();

        CommentsDensityPlugin::promptForPreCommitHook($io);

        $this->assertFileExists('.git/hooks/pre-commit');
        unlink('.git/hooks/pre-commit'); // Clean up after the test
    }
}
