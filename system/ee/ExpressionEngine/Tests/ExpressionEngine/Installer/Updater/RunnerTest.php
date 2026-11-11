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
            'system/ee/removed.txt' => 'old file',
            'system/ee/updater/probe.txt' => 'running updater',
            'system/user/config.php' => 'site configuration',
            'themes/ee/theme.txt' => 'old theme',
            'themes/user/theme.txt' => 'user theme',
        ];
        $this->newFiles = [
            'system/ee/core.txt' => 'new core',
            'system/ee/added.txt' => 'new file',
            'themes/ee/theme.txt' => 'new theme',
        ];
        foreach ($this->originalFiles as $path => $contents) {
            $this->writeFile($this->directory . $path, $contents);
        }
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
            if (strpos($path, $this->directory) !== 0) {
                throw new \RuntimeException('Updater path outside its fixture: ' . $path);
            }
            return $path;
        });
        $logger = new Logger($this->directory . 'cache/ee_update/update.log', $filesystem);
        $updater = new FileUpdater($filesystem, new Verifier($filesystem), $logger);
        $this->runner = new class($updater) extends Runner {
            private $fileUpdater;

            public function __construct($fileUpdater)
            {
                $this->fileUpdater = $fileUpdater;
                parent::__construct();
            }

            protected function makeUpdaterService()
            {
                return $this->fileUpdater;
            }
        };
    }

    protected function tearDown(): void
    {
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
        $this->assertFalse(function_exists('file_exists_safe'));
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

    private function writeFile($path, $contents)
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }
        file_put_contents($path, $contents);
    }
}
