<?php

namespace ExpressionEngine\Tests\Cli\Commands;

use ExpressionEngine\Cli\Commands\CommandUpdate;
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
}

class UpdateCommandFailure extends \RuntimeException {}

class UpdateConfigStub
{
    public $values = ['app_version' => '7.5.28'];
    public $writes = [];
    public function item($key) { return isset($this->values[$key]) ? $this->values[$key] : false; }
    public function set($key, $value, $write = false) {
        $this->values[$key] = $value;
        $this->writes[] = [$key, $value, $write];
    }
}

class UpdateCommandStub extends CommandUpdate
{
    public $flags = [];
    public $events = [];
    public $pending = false;
    public $versionAtMigration;
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->currentVersion = '7.5.28';
        $this->updateVersion = '7.5.29';
        $this->updateType = 'curl';
        $this->defaultToYes = true;
    }

    public function download() { return $this->upgradeFromDownloadedVersion(); }
    protected function option($name, $default = null) { return isset($this->flags[$name]) ? $this->flags[$name] : $default; }
    protected function initUpgrade() { $this->events[] = 'initialize'; }
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
