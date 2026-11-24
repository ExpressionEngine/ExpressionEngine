<?php

namespace ExpressionEngine\Tests\Cli\Commands;

use ExpressionEngine\Cli\Commands\CommandUpdate;
use ExpressionEngine\Service\Updater\UpdaterException;
use PHPUnit\Framework\TestCase;

class CommandUpdateTest extends TestCase
{
    private $config;
    private $command;
    private $extensions;

    protected function setUp(): void
    {
        $this->config = new UpdateConfigStub();
        $this->command = new UpdateCommandStub($this->config);
        $this->extensions = new class {
            public $enabled = true;
            public $calls = [];
            public $error;
            public function active_hook($hook) { return $this->enabled; }
            public function call($hook) {
                $this->calls[] = $hook;
                if ($this->error) { throw $this->error; }
            }
        };
        ee()->setMock('config', $this->config);
        ee()->setMock('Config', new class($this->config) {
            private $config;
            public function __construct($config) { $this->config = $config; }
            public function getFile() { return $this->config; }
        });
        ee()->setMock('cache', new class { public function save($key, $value) {} });
        ee()->setMock('Updater/Runner', new class { public function run() {} });
        ee()->setMock('extensions', $this->extensions);
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testDownloadedUpdateKeepsOriginalVersionUntilMigrationCompletes()
    {
        $this->command->download();
        $this->assertSame('7.5.28', $this->command->versionAtMigration);
        $this->assertSame([], $this->config->writes);
    }

    public function testDownloadFailureDoesNotAdvanceTheVersion()
    {
        ee()->setMock('Updater/Runner', new class {
            public function run() { throw new \RuntimeException('Download failed'); }
        });
        try {
            $this->command->download();
            $this->fail('The download failure was ignored.');
        } catch (UpdateCommandFailure $e) {
            $this->assertStringContainsString('Download failed', $e->getMessage());
        }
        $this->assertSame('7.5.28', $this->config->item('app_version'));
        $this->assertNull($this->command->versionAtMigration);
    }

    public function testRollbackBypassesVersionDiscoveryAndNormalUpdateChecks()
    {
        $this->command->flags['--rollback'] = true;
        $this->command->pending = true;
        $this->command->handle();
        $this->assertSame(['rollback'], $this->command->events);
        $this->assertSame([], $this->extensions->calls);
    }

    /** @dataProvider pendingCommands */
    public function testPendingUpdateCannotBeReplacedOrCompleted($flags)
    {
        $this->command->flags = $flags;
        $this->command->pending = true;
        $this->expectException(UpdateCommandFailure::class);
        $this->expectExceptionMessage('rollback');
        $this->command->handle();
    }

    public function pendingCommands()
    {
        return [
            'new update' => [[]],
            'completion' => [['--complete' => true]],
        ];
    }

    public function testCompletionUsesTheInstalledApplicationWithoutLookingUpAnotherRelease()
    {
        $this->command->flags['--complete'] = true;
        $this->command->handle();
        $this->assertSame(['postflight', 'complete'], $this->command->events);
        $this->assertSame(['updater_complete'], $this->extensions->calls);
    }

    public function testCompletionRespectsDisabledExtensions()
    {
        $this->extensions->enabled = false;
        $this->command->flags['--complete'] = true;
        $this->command->handle();
        $this->assertSame([], $this->extensions->calls);
    }

    public function testCompletionErrorsAreReportedAsFailures()
    {
        $this->extensions->error = new \Error('Completion hook failed');
        $this->command->flags['--complete'] = true;
        $this->expectException(UpdateCommandFailure::class);
        $this->expectExceptionMessage('Completion hook failed');
        $this->command->handle();
    }

    public function testDownloadedUpdateDoesNotRepeatTheFreshProcessCompletionHook()
    {
        $this->command->handle();
        $this->assertSame(['initialize', 'upgrade', 'complete'], $this->command->events);
        $this->assertSame([], $this->extensions->calls);
    }

    public function testLocalUpdateStillRunsCompletion()
    {
        $this->command->updateType = 'local';
        $this->command->handle();
        $this->assertSame(['initialize', 'upgrade', 'postflight', 'complete'], $this->command->events);
        $this->assertSame(['updater_complete'], $this->extensions->calls);
    }

    /** @dataProvider waysToUpdateASiteThatLeavesMajorUpgradesToComposer */
    public function testOnlyADownloadedReleaseIsHeldToTheRuleForOneClickUpdates($flags, $updateType, $events)
    {
        $rule = new class {
            public $asked = [];
            public function checkComposerManagedCore($target)
            {
                $this->asked[] = $target;
                throw new UpdaterException('Use Composer to upgrade');
            }
        };
        ee()->setMock('Updater/PrepMajorUpgrade', $rule);
        $this->command->flags = $flags;
        $this->command->updateType = $updateType;
        $held = ($updateType === 'curl');

        try {
            $this->command->handle();
            $this->assertFalse($held, 'A downloaded release was applied without asking');
        } catch (UpdateCommandFailure $e) {
            $this->assertTrue($held, $e->getMessage());
            $this->assertStringContainsString('Use Composer to upgrade', $e->getMessage());
        }

        $this->assertSame($held ? ['7.6.0'] : [], $rule->asked);
        $this->assertSame($events, $this->command->events);
    }

    public function waysToUpdateASiteThatLeavesMajorUpgradesToComposer()
    {
        $package = ['--package' => 'ee.zip', '--signature' => 'ee.sig', '--expect-version' => '7.5.29'];

        return [
            'the latest release, downloaded' => [[], 'curl', ['initialize']],
            'a supplied package' => [$package, 'package', ['initialize', 'upgrade', 'complete']],
            'new files already in place' => [[], 'local', ['initialize', 'upgrade', 'postflight', 'complete']],
        ];
    }

    /** @dataProvider incompletePackageOptions */
    public function testAPackageIsSuppliedWithItsSignatureAndExpectedVersionOrNotAtAll($flags)
    {
        $this->command->flags = $flags;

        try {
            $this->command->handle();
            $this->fail('An update started with only some of the package options');
        } catch (UpdateCommandFailure $e) {
            $this->assertStringContainsString('package_options_together', $e->getMessage());
        }

        $this->assertSame([], $this->command->events);
    }

    public function incompletePackageOptions()
    {
        return [
            'package alone' => [['--package' => 'ee.zip']],
            'a package named 0, alone' => [['--package' => '0']],
            'no expected version' => [['--package' => 'ee.zip', '--signature' => 'ee.sig']],
            'no signature' => [['--package' => 'ee.zip', '--expect-version' => '7.5.29']],
            'no package' => [['--signature' => 'ee.sig', '--expect-version' => '7.5.29']],
        ];
    }

    /** @dataProvider packageVersions */
    public function testASuppliedPackageIsOnlyAppliedWhenItIsNewerThanTheSite($packageVersion, $events, $failure)
    {
        $this->command->flags = ['--package' => 'ee.zip', '--signature' => 'ee.sig', '--expect-version' => $packageVersion];
        $this->command->updateType = 'package';
        $this->command->updateVersion = $packageVersion;

        try {
            $this->command->handle();
            $this->assertNull($failure, 'An older package was reported as applied');
        } catch (UpdateCommandFailure $e) {
            $this->assertNotNull($failure, $e->getMessage());
            $this->assertStringContainsString($failure, $e->getMessage());
        }

        $this->assertSame($events, $this->command->events);
    }

    public function packageVersions()
    {
        return [
            'newer than the site' => ['7.5.29', ['initialize', 'upgrade', 'complete'], null],
            'the version already installed' => ['7.5.28', ['initialize', 'complete'], null],
            'older than the site' => ['7.5.27', ['initialize'], 'package_is_older'],
            'an older major version' => ['6.4.17', ['initialize'], 'package_is_older'],
        ];
    }

    public function testASuppliedPackageIsHandedToTheUpdaterBeforeItRuns()
    {
        $runner = new class {
            public $events = [];
            public function usePackage(array $package) { $this->events[] = ['package', $package]; }
            public function run() { $this->events[] = 'run'; }
        };
        ee()->setMock('Updater/Runner', $runner);

        $package = ['path' => 'copy.zip', 'signature' => 'signature', 'hash' => 'hash', 'folder' => 'copy/'];
        $this->command->supply($package);
        $this->command->download();

        $this->assertSame([['package', $package], 'run'], $runner->events);
    }

    public function testAnUpdateWhoseFinishingStepsAreDueStopsAnotherFromStarting()
    {
        $this->config->values['updater_completion_pending'] = '7.5.28';

        try {
            $this->command->handle();
            $this->fail('Another update started before the last one was finished');
        } catch (UpdateCommandFailure $e) {
            $this->assertStringContainsString('completion_pending', $e->getMessage());
        }

        $this->assertSame([], $this->command->events);
        $this->assertSame('7.5.28', $this->config->item('updater_completion_pending'));
    }

    /** @dataProvider updatesWithARecordOfUnfinishedSteps */
    public function testOnlyUpdatesThatClearWhatWasKeptAreHeldUntilTheLastOneIsFinished($flags, $installerPresent, $held)
    {
        $this->config->values['updater_completion_pending'] = '7.5.28';
        $this->command->flags = $flags;
        $this->command->installerPresent = $installerPresent;

        try {
            $this->command->handle();
            $this->assertFalse($held, 'An update that clears what the last one kept was allowed to start');
        } catch (UpdateCommandFailure $e) {
            $this->assertTrue($held, $e->getMessage());
            $this->assertStringContainsString('completion_pending', $e->getMessage());
        }

        $this->assertSame('7.5.28', $this->config->item('updater_completion_pending'));
    }

    public function updatesWithARecordOfUnfinishedSteps()
    {
        $package = ['--package' => 'ee.zip', '--signature' => 'ee.sig', '--expect-version' => '7.5.29'];

        return [
            'a downloaded update' => [[], false, true],
            'a supplied package' => [$package, false, true],
            'a supplied package, with new files also dropped in' => [$package, true, true],
            'a supplied package named 0, with new files also dropped in' => [['--package' => '0'] + $package, true, true],
            'new files dropped in' => [[], true, false],
        ];
    }

    /** @dataProvider updateKinds */
    public function testNothingIsAppliedToASiteThatWasUpdatedWhileTheCommandWaited($flags, $updateType)
    {
        $this->command->flags = $flags;
        $this->command->updateType = $updateType;

        // Another update finished after this command read the installed version
        $this->command->whileWaiting = function () {
            $this->command->versionOnDisk = '7.5.30';
        };

        try {
            $this->command->handle();
            $this->fail('An update was applied to a site that had changed since it was checked');
        } catch (UpdateCommandFailure $e) {
            $this->assertStringContainsString('site_changed_while_waiting', $e->getMessage());
        }

        $this->assertSame(['initialize'], $this->command->events);
    }

    public function updateKinds()
    {
        return [
            'a supplied package' => [['--package' => 'ee.zip', '--signature' => 'ee.sig', '--expect-version' => '7.5.29'], 'package'],
            'a downloaded update' => [[], 'curl'],
        ];
    }

    /** @dataProvider configFiles */
    public function testAnUpdateFinishedElsewhereIsNoticedHoweverTheConfigFileIsWritten($before, $after)
    {
        $this->config->config_path = tempnam(sys_get_temp_dir(), 'ee-config');
        file_put_contents($this->config->config_path, $before);

        $this->command->whileWaiting = function () use ($after) {
            file_put_contents($this->config->config_path, $after);
        };

        try {
            $this->command->handle();
            $this->fail('An update was applied to a site that had changed since it was checked');
        } catch (UpdateCommandFailure $e) {
            $this->assertStringContainsString('site_changed_while_waiting', $e->getMessage());
        } finally {
            @unlink($this->config->config_path);
        }

        $this->assertSame(['initialize'], $this->command->events);
    }

    public function configFiles()
    {
        $written = function ($version, $quote = "'") {
            return "<?php\n\$config['save_tmpl_files'] = 'n';\n\$config[{$quote}app_version{$quote}] = {$quote}{$version}{$quote};\n";
        };

        return [
            'as the updater writes it' => [$written('7.5.28'), $written('7.5.30')],
            'double quotes' => [$written('7.5.28', '"'), $written('7.5.30', '"')],
            'a file that is never run' => ["<?php exit('No direct script access allowed');\n\$config['app_version'] = '7.5.28';", "<?php exit('No direct script access allowed');\n\$config['app_version'] = '7.5.30';"],
            'an earlier version noted in a comment' => [$written('7.5.28') . "// \$config['app_version'] = '7.5.20';\n", $written('7.5.30') . "// \$config['app_version'] = '7.5.20';\n"],
            'a version that is first written while waiting' => ["<?php \$config['site_label'] = 'Site';", $written('7.5.30')],
        ];
    }

    /** @dataProvider configFilesSayingMoreThanTheInstalledVersion */
    public function testAVersionMentionedElsewhereInTheConfigFileDoesNotHoldUpAnUpdate($contents)
    {
        $this->config->config_path = tempnam(sys_get_temp_dir(), 'ee-config');

        if ($contents === null) {
            unlink($this->config->config_path);
        } else {
            file_put_contents($this->config->config_path, $contents);
        }

        try {
            $this->command->handle();
        } finally {
            @unlink($this->config->config_path);
        }

        $this->assertContains('upgrade', $this->command->events);
    }

    public function configFilesSayingMoreThanTheInstalledVersion()
    {
        $installed = "<?php\n\$config['app_version'] = '7.5.28';\n";

        return [
            'an earlier version noted in a comment' => [$installed . "// Was: \$config['app_version'] = '7.5.20';\n"],
            'a version for somewhere else' => [$installed . "if (getenv('EE_ENV') === 'elsewhere') {\n    \$config['app_version'] = '7.5.20';\n}\n"],
            'no version in the file' => ["<?php \$config['site_label'] = 'Site';"],
            'no file' => [null],
        ];
    }

    public function testASecondUpdateDoesNotStartWhileOneIsRunning()
    {
        // The first command is still at work: it has the lock and has not finished
        $first = new UpdateCommandStub($this->config);
        $first->lockPath = $this->command->lockPath;
        $first->handle();

        try {
            $this->command->handle();
            $this->fail('A second update started while the first was still running');
        } catch (UpdateCommandFailure $e) {
            $this->assertStringContainsString('already_running', $e->getMessage());
        }

        $this->assertSame([], $this->command->events);

        // Once the first is gone, however it went, the next can run
        unset($first);
        gc_collect_cycles();
        $this->command->handle();

        $this->assertSame(['initialize', 'upgrade', 'complete'], $this->command->events);
    }

    /** @dataProvider stepsOfARunningUpdate */
    public function testFinishingAndRollingBackAreNotHeldUpByTheUpdateTheyBelongTo($flags, $pending, $events)
    {
        // An update finishes itself by running this command again while it still holds the lock
        $running = new UpdateCommandStub($this->config);
        $running->lockPath = $this->command->lockPath;
        $running->handle();

        $this->command->flags = $flags;
        $this->command->pending = $pending;
        $this->command->handle();

        $this->assertSame($events, $this->command->events);
    }

    public function stepsOfARunningUpdate()
    {
        return [
            'finishing' => [['--complete' => true], false, ['postflight', 'complete']],
            'rolling back' => [['--rollback' => true], true, ['rollback']],
        ];
    }

    public function testAnUpdateStillRunsWhereNoLockCanBeHad()
    {
        $this->command->lockPath = sys_get_temp_dir() . '/no-such-folder-' . bin2hex(random_bytes(8)) . '/ee_update.lock';

        $this->command->handle();

        $this->assertSame(['initialize', 'upgrade', 'complete'], $this->command->events);
    }

    public function testRollbackIsOfferedInsteadWhileTheUpdaterIsStillInPlace()
    {
        $this->config->values['updater_completion_pending'] = '7.5.28';
        $this->command->pending = true;

        $this->expectException(UpdateCommandFailure::class);
        $this->expectExceptionMessage('rollback');
        $this->command->handle();
    }

    public function testAFinishingRecordForAnEarlierVersionNoLongerApplies()
    {
        // The site has since been updated another way
        $this->config->values['updater_completion_pending'] = '7.5.27';

        $this->command->handle();

        $this->assertSame(['initialize', 'upgrade', 'complete'], $this->command->events);
        $this->assertFalse($this->config->item('updater_completion_pending'));
    }

    public function testFinishingAnUpdateClearsItsRecordAndWhatWasKeptForIt()
    {
        $kept = $this->keepUpdateFiles();
        $this->config->values['updater_completion_pending'] = '7.5.28';
        $this->command->flags['--complete'] = true;

        $this->command->handle();

        $this->assertSame(['postflight', 'complete'], $this->command->events);
        $this->assertSame(['updater_complete'], $this->extensions->calls);
        $this->assertFalse($this->config->item('updater_completion_pending'));
        $this->assertDirectoryDoesNotExist($kept);
    }

    public function testAFinishingStepThatFailsLeavesItsRecordAndWhatWasKept()
    {
        $kept = $this->keepUpdateFiles();
        $this->config->values['updater_completion_pending'] = '7.5.28';
        $this->extensions->error = new \Error('Completion hook failed');
        $this->command->flags['--complete'] = true;

        try {
            $this->command->handle();
            $this->fail('The completion failure was ignored.');
        } catch (UpdateCommandFailure $e) {
            $this->assertStringContainsString('Completion hook failed', $e->getMessage());
        }

        $this->assertSame('7.5.28', $this->config->item('updater_completion_pending'));
        $this->assertFileExists($kept . 'backups/core.txt');

        unlink($kept . 'backups/core.txt');
        rmdir($kept . 'backups');
        rmdir($kept);
    }

    public function testFinishingWithNothingRecordedLeavesTheUpdateFolderAlone()
    {
        $kept = $this->keepUpdateFiles();
        $this->command->flags['--complete'] = true;

        $this->command->handle();

        $this->assertFileExists($kept . 'backups/core.txt');

        unlink($kept . 'backups/core.txt');
        rmdir($kept . 'backups');
        rmdir($kept);
    }

    /**
     * Stands in for the backups an update keeps until its finishing steps succeed
     */
    private function keepUpdateFiles()
    {
        $folder = sys_get_temp_dir() . '/ee-update-kept-' . bin2hex(random_bytes(8)) . '/';
        mkdir($folder . 'backups', 0700, true);
        file_put_contents($folder . 'backups/core.txt', 'old core');
        $this->command->workingPath = $folder;

        ee()->setMock('Filesystem', new class {
            public function deleteDir($path)
            {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($files as $file) {
                    $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
                }
                rmdir($path);
            }
        });

        return $folder;
    }
}

class UpdateCommandFailure extends \RuntimeException {}

class UpdateConfigStub
{
    public $values = ['app_version' => '7.5.28'];
    public $config_path;
    public $writes = [];
    public function item($key) { return isset($this->values[$key]) ? $this->values[$key] : false; }
    public function set($key, $value, $write = false) {
        $this->values[$key] = $value;
        $this->writes[] = [$key, $value, $write];
    }
    public function _update_config($values = [], $remove = []) {
        $this->values = array_merge($this->values, $values);
        foreach ($remove as $key => $value) { unset($this->values[$key]); }
        return true;
    }
}

class UpdateCommandStub extends CommandUpdate
{
    public $flags = [];
    public $events = [];
    public $pending = false;
    public $versionAtMigration;
    public $workingPath = '/no/update/working/folder/';
    public $installerPresent = false;
    public $versionOnDisk;
    public $whileWaiting;
    public $lockPath;
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->currentVersion = '7.5.28';
        $this->updateVersion = '7.6.0';
        $this->updateType = 'curl';
        $this->defaultToYes = true;
        $this->lockPath = sys_get_temp_dir() . '/ee-update-' . bin2hex(random_bytes(8)) . '.lock';
    }

    public function __destruct()
    {
        if (is_resource($this->lock)) {
            fclose($this->lock);
        }

        @unlink($this->lockPath);
    }

    public function download() { return $this->upgradeFromDownloadedVersion(); }
    public function supply(array $package) { $this->package = $package; }
    protected function updateWorkingPath() { return $this->workingPath; }
    protected function doesInstallerFolderExist() { return $this->installerPresent; }
    protected function versionsInConfigFile() {
        if ($this->versionOnDisk !== null) { return [$this->versionOnDisk]; }
        return $this->config->config_path ? parent::versionsInConfigFile() : [(string) $this->config->item('app_version')];
    }
    protected function updateLockPath() { return $this->lockPath; }
    protected function option($name, $default = null) { return isset($this->flags[$name]) ? $this->flags[$name] : $default; }
    protected function initUpgrade() {
        $this->events[] = 'initialize';
        if ($this->whileWaiting) { ($this->whileWaiting)(); }
    }
    protected function hasPendingUpdate() { return $this->pending; }
    protected function runRecovery() { $this->events[] = 'rollback'; }
    protected function runUpgrade() { $this->events[] = 'upgrade'; }
    protected function runUpdater($step = null, $microapp = false, $noBootstrap = false, $rollback = false) {
        $this->versionAtMigration = $this->config->item('app_version');
    }
    protected function postFlightCheck() { $this->events[] = 'postflight'; }
    public function info($message = null) {}
    public function complete($message = null) { $this->events[] = 'complete'; }
    public function fail($messages = null) { throw new UpdateCommandFailure(is_array($messages) ? implode("\n", $messages) : $messages); }
}
