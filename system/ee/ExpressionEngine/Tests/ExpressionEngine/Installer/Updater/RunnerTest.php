<?php

namespace ExpressionEngine\Tests\Installer\Updater;

use ExpressionEngine\Updater\Library\Filesystem\Filesystem;
use ExpressionEngine\Updater\Service\Updater\FileUpdater;
use ExpressionEngine\Updater\Service\Updater\Logger;
use ExpressionEngine\Updater\Service\Updater\Runner;
use ExpressionEngine\Updater\Service\Updater\UpdaterException;
use ExpressionEngine\Updater\Service\Updater\Verifier;
use PHPUnit\Framework\TestCase;

/**
 * The downloaded updater must work while the old application's helpers are loaded.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RunnerTest extends TestCase
{
    private $directory;
    private $archive;
    private $originalFiles;
    private $newFiles;
    private $runner;
    private $config;
    private $messages = [];

    protected function setUp(): void
    {
        $this->assertFalse(function_exists('file_exists_safe'));
        $this->directory = sys_get_temp_dir() . '/ee-updater-' . bin2hex(random_bytes(8)) . '/';
        $this->archive = $this->directory . 'cache/ee_update/archive/';
        mkdir($this->directory, 0700);

        // Scope only the updater's paths to this fixture; keep PHPUnit's global paths intact.
        define('ExpressionEngine\Updater\Service\Updater\SYSPATH', $this->directory . 'system/');
        define('ExpressionEngine\Updater\Service\Updater\PATH_CACHE', $this->directory . 'cache/');
        define('REQ', 'CLI');
        define('CLI_VERBOSE', false);
        require_once BASEPATH . 'helpers/cli_helper.php';

        $this->config = new RunnerConfigStub();
        ee()->setMock('config', $this->config);
        ee()->setMock('Config', new class($this->config) {
            private $config;
            public function __construct($config) { $this->config = $config; }
            public function getFile() { return $this->config; }
        });

        // These dependencies are copied into the updater namespace by release packaging.
        foreach ([
            'Service/Logger/File.php',
            'Service/Updater/Logger.php',
            'Service/Updater/SteppableTrait.php',
            'Service/Updater/Verifier.php',
        ] as $path) {
            $source = file_get_contents(SYSPATH . 'ee/ExpressionEngine/' . $path);
            $source = preg_replace('/(namespace|use) ExpressionEngine\\\\/i', '$1 ExpressionEngine\\Updater\\', $source);
            $file = $this->directory . basename($path);
            file_put_contents($file, $source);
            require $file;
        }

        $this->originalFiles = [
            'system/ee/core.txt' => 'old core',
            'system/ee/legacy/libraries/Core.php' => "<?php define('APP_VER', '7.5.28');",
            'system/ee/removed.txt' => 'old file',
            'system/ee/updater/probe.txt' => 'running updater',
            'system/user/config.php' => 'site configuration',
            'themes/ee/theme.txt' => 'old theme',
            'themes/user/theme.txt' => 'user theme',
        ];
        $this->newFiles = [
            'system/ee/core.txt' => 'new core',
            'system/ee/legacy/libraries/Core.php' => "<?php define('APP_VER', '7.5.29');",
            'system/ee/added.txt' => 'new file',
            'themes/ee/theme.txt' => 'new theme',
        ];
        foreach ($this->originalFiles as $path => $contents) {
            $this->writeFile($this->directory . $path, $contents);
        }
        $this->writeFile($this->directory . 'system/ee/updater/compatibility.php',
            file_get_contents(SYSPATH . 'ee/installer/updater/compatibility.php'));
        $manifest = [];
        foreach ($this->newFiles as $path => $contents) {
            $this->writeFile($this->archive . $path, $contents);
            $manifest[$path] = hash('sha384', $contents);
        }
        $this->writeFile($this->directory . 'system/ee/updater/hash-manifest', json_encode($manifest));
        $this->writeFile($this->directory . 'cache/ee_update/configs.json', json_encode([
            'archive_path' => rtrim($this->archive, '/'),
            'theme_paths' => [$this->directory . 'themes'],
        ]));

        // Use real filesystem operations, but fail before touching anything outside the fixture.
        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->onlyMethods(['normalize'])
            ->getMock();
        $filesystem->method('normalize')->willReturnCallback(function ($path) {
            $normalizedPath = str_replace('\\', '/', $path);
            $normalizedDirectory = str_replace('\\', '/', $this->directory);
            if (strpos($normalizedPath, $normalizedDirectory) !== 0) {
                throw new \RuntimeException('Updater path outside its fixture: ' . $path);
            }
            return $path;
        });
        $logger = new Logger($this->directory . 'cache/ee_update/update.log', $filesystem);
        $updater = new FileUpdater($filesystem, new Verifier($filesystem), $logger);
        $this->runner = new class($updater) extends Runner {
            private $fileUpdater;
            public $completions = 0;
            public $completionError;
            public $completionRollbacks = [];

            public function __construct($fileUpdater)
            {
                $this->fileUpdater = $fileUpdater;
                parent::__construct();
            }

            protected function makeUpdaterService()
            {
                return $this->fileUpdater;
            }

            protected function completeCliUpdate($rollback = false)
            {
                $this->completions++;
                $this->completionRollbacks[] = $rollback;
                if ($this->completionError) {
                    throw $this->completionError;
                }
            }

            public function makeDatabaseUpdaterService()
            {
                return parent::makeDatabaseUpdaterService();
            }
        };
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
        if ($this->directory && is_dir($this->directory)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->directory);
        }
    }

    public function testLoadsExceptionsForOlderCliCommandsWithoutAnUpdaterAutoloader()
    {
        $this->assertTrue(class_exists(UpdaterException::class, false));
    }

    public function testInitializesAndLogsWithoutNewApplicationHelpers()
    {
        $runner = new Runner();
        $this->assertSame('updateFiles', $runner->getFirstStep());
        $logger = new Logger($this->directory . 'cache/ee_update/probe.log', new Filesystem());
        $logger->log('bootstrap compatibility');
        $this->assertStringContainsString('bootstrap compatibility', file_get_contents($this->directory . 'cache/ee_update/probe.log'));
        $this->assertFalse(function_exists('file_exists_safe'));
    }

    public function testFilesystemChecksDoNotRequireApplicationHelpers()
    {
        $filesystem = new Filesystem();
        foreach ([$this->directory, $this->directory . 'system/ee/core.txt'] as $path) {
            $this->assertTrue($filesystem->exists($path));
            foreach (["\0" . $path, $path . "\0", $path . "\0suffix"] as $invalid) {
                $this->assertFalse($filesystem->exists($invalid));
            }
        }
        $this->assertFalse($filesystem->exists(''));
        $this->assertFalse($filesystem->exists($this->directory . 'missing'));
    }

    public function testReplacesFilesAndPreservesSiteFiles()
    {
        $this->runner->updateFiles();
        foreach ($this->newFiles as $path => $contents) {
            $this->assertSame($contents, file_get_contents($this->directory . $path), $path);
        }
        $this->assertFileDoesNotExist($this->directory . 'system/ee/removed.txt');
        $this->assertSame('old core', file_get_contents($this->directory . 'cache/ee_update/backups/system_ee/core.txt'));
        foreach (['system/ee/updater/probe.txt', 'system/user/config.php', 'themes/user/theme.txt'] as $path) {
            $this->assertSame($this->originalFiles[$path], file_get_contents($this->directory . $path), $path);
        }
        $this->assertTrue(function_exists('file_exists_safe'));
        $this->assertFalse(file_exists_safe($this->directory . 'system/ee/core.txt' . "\0"));
    }

    public function testMakesNewHelpersAvailableToLateLoadedCoreClasses()
    {
        $this->runner->updateFiles();
        $mime = new \ExpressionEngine\Library\Mime\MimeType();
        $this->assertSame('text/plain', $mime->ofFile($this->directory . 'system/ee/core.txt'));
    }

    public function testRollsBackAfterInstalledFileVerificationFails()
    {
        file_put_contents($this->archive . 'system/ee/core.txt', 'damaged core');
        try {
            $this->runner->updateFiles();
            $this->fail('The updater accepted a damaged file.');
        } catch (UpdaterException $e) {
            $this->assertSame(10, $e->getCode());
            $this->assertStringContainsString('system/ee/core.txt', $e->getMessage());
        }
        $this->runner->rollback();
        foreach ($this->originalFiles as $path => $contents) {
            $this->assertSame($contents, file_get_contents($this->directory . $path), $path);
        }
        $this->assertFileDoesNotExist($this->directory . 'system/ee/added.txt');
        $this->assertFalse(function_exists('file_exists_safe'));
    }

    public function testOlderCliHandoffRestoresTheSourceVersionBeforeReplacingFiles()
    {
        $this->config->config['app_version'] = '7.5.29';
        file_put_contents($this->archive . 'system/ee/core.txt', 'damaged core');
        try {
            $this->runner->updateFiles();
            $this->fail('The damaged package was accepted.');
        } catch (UpdaterException $e) {
            $this->assertSame(10, $e->getCode());
        }
        $state = json_decode(file_get_contents($this->directory . 'cache/ee_update/configs.json'), true);
        $this->assertSame('7.5.28', $state['from_version']);
        $this->assertSame('7.5.29', $state['to_version']);
        $this->assertSame('7.5.28', $this->config->item('app_version'));
    }

    /** @dataProvider onlineStates */
    public function testSuccessfulCompletionRecordsTheTargetAndPreservesOnlineState($online)
    {
        $this->config->config['is_system_on_before_updater'] = $online;
        $this->runner->updateFiles();
        $this->assertSame('7.5.28', $this->config->item('app_version'));
        $this->mockCompletionServices();
        $this->runner->selfDestruct();
        $this->assertSame('7.5.29', $this->config->item('app_version'));
        $this->assertSame($online, $this->config->item('is_system_on'));
        $this->assertFalse($this->config->item('is_system_on_before_updater'));
        $this->assertSame(1, $this->runner->completions);
        $this->assertSame(['Update complete. Now running version 7.5.29'], $this->messages);
    }

    public function onlineStates()
    {
        return [['y'], ['n']];
    }

    /**
     * Verify the installed version and backups survive a CLI completion failure.
     *
     * @return void
     */
    public function testCompletionFailureRetainsTheBackups()
    {
        $this->runner->updateFiles();
        $this->mockCompletionServices();
        ee()->setMock('Filesystem', new class(function ($path) {
            $this->assertSame(0, strpos($path, $this->directory));
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($path);
        }) {
            private $remove;
            public function __construct($remove) { $this->remove = $remove; }
            public function deleteDir($path) { ($this->remove)($path); }
        });
        $this->runner->completionError = new \RuntimeException('Completion hook failed');
        try {
            $this->runner->runStep('selfDestruct');
            $this->fail('The completion failure was ignored.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Completion hook failed', $e->getMessage());
        }
        $this->assertSame('7.5.29', $this->config->item('app_version'));
        $this->assertSame('old core', file_get_contents($this->directory . 'cache/ee_update/backups/system_ee/core.txt'));
        $this->assertDirectoryDoesNotExist($this->directory . 'system/ee/updater');
    }

    /**
     * Verify the CLI completes in the current process when passthru is unavailable.
     *
     * @return void
     */
    public function testCompletionWorksWithoutPassthru()
    {
        // This test runs in its own PHP process so the namespaced stub stays local to it.
        require SYSPATH . 'ee/ExpressionEngine/Tests/support/updater_without_passthru.php';
        $this->runner->updateFiles();
        $this->mockCompletionServices();

        $extensions = $this->getMockBuilder(\stdClass::class)->addMethods(['active_hook', 'call'])->getMock();
        $extensions->expects($this->once())->method('active_hook')->with('updater_complete')->willReturn(true);
        $extensions->expects($this->once())->method('call')->with('updater_complete');
        ee()->setMock('extensions', $extensions);

        (new Runner())->selfDestruct();

        $this->assertSame('7.5.29', $this->config->item('app_version'));
        $this->assertSame('y', $this->config->item('is_system_on'));
    }

    public function testRollbackRestoresOriginalVersionWithNewCodeAlreadyLoaded()
    {
        $this->runner->updateFiles();
        define('ExpressionEngine\\Updater\\Service\\Updater\\APP_VER', '7.5.29');
        $this->runner->rollback();
        $this->mockCompletionServices();
        $this->runner->selfDestruct('rollback');
        $this->assertSame('7.5.28', $this->config->item('app_version'));
        $this->assertSame('y', $this->config->item('is_system_on'));
        $this->assertSame(1, $this->runner->completions);
        $this->assertSame([true], $this->runner->completionRollbacks);
        $this->assertSame(['Update rolled back. Now running version 7.5.28'], $this->messages);
        $this->assertSame($this->originalFiles['system/ee/core.txt'], file_get_contents($this->directory . 'system/ee/core.txt'));
    }

    public function testCliCanFinishRollingBackAWebUpdateWithoutCliVersionMetadata()
    {
        define('ExpressionEngine\\Updater\\Service\\Updater\\APP_VER', '7.5.29');
        $this->mockCompletionServices();
        $this->runner->selfDestruct('rollback');
        $this->assertSame('7.5.28', $this->config->item('app_version'));
        $this->assertSame(1, $this->runner->completions);
        $this->assertSame([true], $this->runner->completionRollbacks);
        $this->assertSame(['Update rolled back. Now running version 7.5.28'], $this->messages);
    }

    private function mockCompletionServices()
    {
        ee()->setMock('Filesystem', new class {
            public function deleteDir($path) { return true; }
        });
        ee()->setMock('load', new class {
            public function is_loaded($name) { return true; }
        });
        ee()->setMock('logger', new class(function ($message) { $this->messages[] = $message; }) {
            private $record;
            public function __construct($record) { $this->record = $record; }
            public function updater($message) { ($this->record)($message); }
        });
    }

    /** @dataProvider updateVersions */
    public function testSelectsMigrationsFromTheInstalledVersion($request, $appVersion, $configVersion)
    {
        define('ExpressionEngine\\Updater\\Service\\Updater\\REQ', $request);
        define('ExpressionEngine\\Updater\\Service\\Updater\\APP_VER', $appVersion);
        ee()->setMock('config', new class($configVersion) {
            private $version;
            public function __construct($version) { $this->version = $version; }
            public function item($key) { return $this->version; }
        });
        $this->writeFile($this->directory . 'system/ee/installer/updates/ud_7_05_28.php', '<?php');
        $this->writeFile($this->directory . 'system/ee/installer/updates/ud_7_05_29.php', '<?php');

        $updater = $this->runner->makeDatabaseUpdaterService();
        $this->assertTrue($updater->hasUpdatesToRun());
        $this->assertSame('runUpdateFile[ud_7_05_29.php]', $updater->getFirstStep());
    }

    public function updateVersions()
    {
        return [
            // CLI writes the target config version while the old application is still running.
            'CLI' => ['CLI', '7.5.28', '7.5.29'],
            // External CLI steps reload the new code while config still has the installed version.
            'CLI new process' => ['CLI', '7.5.29', '7.5.28'],
            // CP reloads the new application before writing the target config version.
            'CP' => ['CP', '7.5.29', '7.5.28'],
        ];
    }

    private function writeFile($path, $contents)
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }
        file_put_contents($path, $contents);
    }
}

class RunnerConfigStub
{
    public $config = ['app_version' => '7.5.28', 'is_system_on' => 'n', 'is_system_on_before_updater' => 'y'];
    public function item($key) { return isset($this->config[$key]) ? $this->config[$key] : false; }
    public function get($key, $default = null) { return isset($this->config[$key]) ? $this->config[$key] : $default; }
    public function set($key, $value, $write = false) { $this->config[$key] = $value; }
    public function _update_config($values, $remove = []) {
        $this->config = array_merge($this->config, $values);
        foreach ($remove as $key => $value) { unset($this->config[$key]); }
        return true;
    }
}
