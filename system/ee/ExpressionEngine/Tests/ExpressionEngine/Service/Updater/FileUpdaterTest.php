<?php

namespace ExpressionEngine\Tests\Service\Updater;

use ExpressionEngine\Updater\Service\Updater\FileUpdater;
use ExpressionEngine\Updater\Service\Updater\UpdaterException;
use Mockery;
use PHPUnit\Framework\TestCase;

class FileUpdaterTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected $filesystem;
    protected $verifier;
    protected $logger;
    protected $archive_path;
    protected $backups_path;
    protected $fileupdater;

    public function setUp(): void
    {
        $this->filesystem = Mockery::mock('ExpressionEngine\Updater\Library\Filesystem\Filesystem');
        $this->verifier = Mockery::mock('ExpressionEngine\Updater\Service\Updater\Verifier');
        $this->logger = Mockery::mock('ExpressionEngine\Updater\Service\Updater\Logger');

        $this->logger->shouldReceive('log');

        $configs_path = PATH_CACHE . 'ee_update/configs.json';
        $this->filesystem->shouldReceive('exists')->with($configs_path)->andReturn(true);
        $this->filesystem->shouldReceive('read')->with($configs_path)->andReturn(
            json_encode([
                'update_path' => PATH_CACHE . 'ee_update/',
                'archive_path' => PATH_CACHE . 'ee_update/ExpressionEngine',
                'theme_paths' => ['/themes/']
            ])
        );

        $this->archive_path = PATH_CACHE . 'ee_update/ExpressionEngine/';
        $this->backups_path = PATH_CACHE . 'ee_update/backups/';

        $this->fileupdater = new FileUpdater($this->filesystem, $this->verifier, $this->logger);
    }

    public function testBackupExistingInstallFiles()
    {
        // Single themes folder
        $this->shouldCallMove(
            SYSPATH . 'ee/',
            $this->backups_path . 'system_ee/',
            [SYSPATH . 'ee/updater']
        );
        $this->shouldCallMove(
            $this->expectedThemeInstallPath('/themes/'),
            $this->backups_path . 'themes_ee/'
        );

        $this->fileupdater->backupExistingInstallFiles();

        // Multiple themes folders, but are the same
        $this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/themes/'];
        $this->shouldCallMove(
            SYSPATH . 'ee/',
            $this->backups_path . 'system_ee/',
            [SYSPATH . 'ee/updater']
        );
        $this->shouldCallMove(
            $this->expectedThemeInstallPath('/themes/'),
            $this->backups_path . 'themes_ee/'
        );

        $this->fileupdater->backupExistingInstallFiles();

        // Multiple unique themes folders
        $this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/some/other/site/themes/'];
        $this->shouldCallMove(
            SYSPATH . 'ee/',
            $this->backups_path . 'system_ee/',
            [SYSPATH . 'ee/updater']
        );
        $this->shouldCallMove(
            $this->expectedThemeInstallPath('/themes/'),
            $this->backups_path . 'themes_ee/'
        );

        $this->fileupdater->backupExistingInstallFiles();
    }

    public function testMoveNewInstallFiles()
    {
        // Single themes folder
        $this->shouldCallMove(
            $this->archive_path . 'system/ee/',
            SYSPATH . 'ee/'
        );
        $this->shouldCallMove(
            $this->archive_path . 'themes/ee/',
            $this->expectedThemeInstallPath('/themes/')
        );

        $this->fileupdater->moveNewInstallFiles();

        // Multiple unique themes folders
        $this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/some/other/site/themes/'];
        $this->shouldCallMove(
            $this->archive_path . 'system/ee/',
            SYSPATH . 'ee/'
        );

        foreach ($this->fileupdater->configs['theme_paths'] as $theme_path) {
            $this->shouldCallMove(
                $this->archive_path . 'themes/ee/',
                $this->expectedThemeInstallPath($theme_path),
                [],
                true
            );
        }

        $this->fileupdater->moveNewInstallFiles();
    }

    public function testVerifyNewFiles()
    {
        $hash_manifiest = SYSPATH . 'ee/updater/hash-manifest';
        $exclusions = ['system/ee/installer/updater', 'system/eecli.php'];

        $this->verifier->shouldReceive('verifyPath')->with(
            SYSPATH . 'ee/',
            $hash_manifiest,
            'system/ee',
            $exclusions
        )->andReturn(true)->once();

        $this->verifier->shouldReceive('verifyPath')->with(
            $this->expectedThemeInstallPath('/themes/'),
            $hash_manifiest,
            'themes/ee',
            $exclusions
        )->andReturn(true)->once();

        $this->fileupdater->verifyNewFiles();

        // Multiple unique themes folders
        $this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/some/other/site/themes/'];
        $this->verifier->shouldReceive('verifyPath')->with(
            SYSPATH . 'ee/',
            $hash_manifiest,
            'system/ee',
            $exclusions
        )->andReturn(true)->once();

        foreach ($this->fileupdater->configs['theme_paths'] as $theme_path) {
            $this->verifier->shouldReceive('verifyPath')->with(
                $this->expectedThemeInstallPath($theme_path),
                $hash_manifiest,
                'themes/ee',
                $exclusions
            )->andReturn(true)->once();
        }

        $this->fileupdater->verifyNewFiles();
    }

    public function testRollbackFiles()
    {
        $this->shouldCallRollbackFiles();
        $this->fileupdater->rollbackFiles();

        // Multiple unique themes folders
        $this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/some/other/site/themes/'];
        $this->shouldCallMove(
            SYSPATH . 'ee/',
            $this->archive_path . 'system/ee/',
            [SYSPATH . 'ee/updater']
        );
        foreach ($this->fileupdater->configs['theme_paths'] as $theme_path) {
            $this->shouldCallDelete($this->expectedThemeInstallPath($theme_path));
        }
        $this->shouldCallMove(
            $this->backups_path . 'system_ee/',
            SYSPATH . 'ee/'
        );
        foreach ($this->fileupdater->configs['theme_paths'] as $theme_path) {
            $this->shouldCallMove(
                $this->backups_path . 'themes_ee/',
                $this->expectedThemeInstallPath($theme_path),
                [],
                true
            );
        }

        $this->fileupdater->rollbackFiles();
    }

    public function testMove()
    {
        // move() is protected, so we'll go through the backup method and test
        // via our mocks; we've also already pretty well tested what happens
        // under ideal circumstances, so we'll only manufacture failures here

        $source = SYSPATH . 'ee/';
        $destination = $this->backups_path . 'system_ee/';

        // Destination directory doesn't exist?
        $this->filesystem->shouldReceive('exists')->with($destination)->andReturn(false)->once();
        $this->filesystem->shouldReceive('mkDir')->with($destination, false)->andReturn(true)->once();
        $this->filesystem->shouldReceive('isDir')->with($source)->andReturn(true)->once();
        $this->filesystem->shouldReceive('getDirectoryContents')->with($source)->andReturn([])->once();

        $source = $this->expectedThemeInstallPath('/themes/');
        $destination = $this->backups_path . 'themes_ee/';

        $this->filesystem->shouldReceive('exists')->with($destination)->andReturn(false)->once();
        $this->filesystem->shouldReceive('mkDir')->with($destination, false)->andReturn(true)->once();
        $this->filesystem->shouldReceive('isDir')->with($source)->andReturn(true)->once();
        $this->filesystem->shouldReceive('getDirectoryContents')->with($source)->andReturn([])->once();

        $this->fileupdater->backupExistingInstallFiles();

        // Destination isn't a directory
        $source = SYSPATH . 'ee/';
        $destination = $this->backups_path . 'system_ee/';
        $this->filesystem->shouldReceive('exists')->with($destination)->andReturn(true)->once();
        $this->filesystem->shouldReceive('isDir')->with($destination)->andReturn(false)->once();

        try {
            $this->fileupdater->backupExistingInstallFiles();
            $this->fail();
        } catch (UpdaterException $e) {
            $this->assertEquals(18, $e->getCode());
        }

        // Destination isn't writable
        $source = SYSPATH . 'ee/';
        $destination = $this->backups_path . 'system_ee/';
        $this->filesystem->shouldReceive('exists')->with($destination)->andReturn(true)->once();
        $this->filesystem->shouldReceive('isDir')->with($destination)->andReturn(true)->once();
        $this->filesystem->shouldReceive('isWritable')->with($destination)->andReturn(false)->once();

        try {
            $this->fileupdater->backupExistingInstallFiles();
            $this->fail();
        } catch (UpdaterException $e) {
            $this->assertEquals(21, $e->getCode());
        }

        // Should exclude files
        $this->filesystem->shouldReceive('exists')->with($destination)->andReturn(false)->once();
        $this->filesystem->shouldReceive('mkDir')->with($destination, false)->andReturn(true)->once();
        $this->filesystem->shouldReceive('isDir')->with($source)->andReturn(true)->once();
        $this->filesystem->shouldReceive('getDirectoryContents')->with($source)->andReturn([
            $source . 'index.html',
            $source . 'updater',
            $source . '.DS_Store',
        ])->once();

        $file_path = $source . 'index.html';
        $normalized_file_path = $this->normalizeMovePath($file_path);
        $new_path = $this->expectedMovePath($source, $destination, $file_path);
        $this->filesystem->shouldReceive('isWritable')->with($normalized_file_path)->andReturn(true)->once();
        $this->filesystem->shouldReceive('rename')->with($normalized_file_path, $new_path)->andReturn(true)->once();

        $source = $this->expectedThemeInstallPath('/themes/');
        $destination = $this->backups_path . 'themes_ee/';

        $this->filesystem->shouldReceive('exists')->with($destination)->andReturn(false)->once();
        $this->filesystem->shouldReceive('mkDir')->with($destination, false)->andReturn(true)->once();

        $file_path = $source . 'index.html';
        $this->filesystem->shouldReceive('isDir')->with($source)->andReturn(true)->once();
        $this->filesystem->shouldReceive('getDirectoryContents')->with($source)->andReturn([])->once();

        $this->fileupdater->backupExistingInstallFiles();

        // Should complain if an attempted move path isn't writable
        $source = SYSPATH . 'ee/';
        $destination = $this->backups_path . 'system_ee/';
        $this->filesystem->shouldReceive('exists')->with($destination)->andReturn(false)->once();
        $this->filesystem->shouldReceive('mkDir')->with($destination, false)->andReturn(true)->once();
        $this->filesystem->shouldReceive('isDir')->with($source)->andReturn(true)->once();
        $this->filesystem->shouldReceive('getDirectoryContents')->with($source)->andReturn([
            $source . 'index.html',
        ])->once();

        $file_path = $source . 'index.html';
        $normalized_file_path = $this->normalizeMovePath($file_path);
        $this->filesystem->shouldReceive('isWritable')->with($normalized_file_path)->andReturn(false)->once();

        try {
            $this->fileupdater->backupExistingInstallFiles();
            $this->fail();
        } catch (UpdaterException $e) {
            $this->assertEquals(19, $e->getCode());
        }
    }

    public function testDelete()
    {
        // We just want to test that it fails to delete if not writable, we've
        // tested the function works under correct circumstances elsewhere

        // Multiple unique themes folders
        $this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/some/other/site/themes/'];
        $this->shouldCallMove(
            SYSPATH . 'ee/',
            $this->archive_path . 'system/ee/',
            [SYSPATH . 'ee/updater']
        );
        $directory = $this->expectedThemeInstallPath('/themes/');
        $this->filesystem->shouldReceive('getDirectoryContents')->with($directory)->andReturn([$directory . 'index.html'])->once();
        $this->filesystem->shouldReceive('isWritable')->with($directory . 'index.html')->andReturn(false)->once();

        try {
            $this->fileupdater->rollbackFiles();
            $this->fail();
        } catch (UpdaterException $e) {
            $this->assertEquals(20, $e->getCode());
        }
    }

    protected function shouldCallRollbackFiles()
    {
        $this->shouldCallMove(
            SYSPATH . 'ee/',
            $this->archive_path . 'system/ee/',
            [SYSPATH . 'ee/updater']
        );

        $this->shouldCallMove(
            $this->expectedThemeInstallPath('/themes/'),
            $this->archive_path . 'themes/ee/'
        );

        $this->shouldCallMove(
            PATH_CACHE . 'ee_update/backups/system_ee/',
            SYSPATH . 'ee/'
        );

        $this->shouldCallMove(
            PATH_CACHE . 'ee_update/backups/themes_ee/',
            $this->expectedThemeInstallPath('/themes/'),
            [],
            true
        );
    }

    protected function shouldCallMove($source, $destination, array $exclusions = [], $copy = false)
    {
        $this->filesystem->shouldReceive('exists')->with($this->pathArgument($destination))->andReturn(true)->once();
        $this->filesystem->shouldReceive('isDir')->with($this->pathArgument($destination))->andReturn(true)->once();
        $this->filesystem->shouldReceive('isWritable')->with($this->pathArgument($destination))->andReturn(true)->once();
        $this->filesystem->shouldReceive('isDir')->with($this->pathArgument($source))->andReturn(true)->once();

        $file_path = $source . 'index.html';
        $this->filesystem->shouldReceive('getDirectoryContents')
            ->with($this->pathArgument($source))
            ->andReturnUsing(function ($actualSource) {
                return [$actualSource . 'index.html'];
            })
            ->once();

        $normalized_file_path = $this->normalizeMovePath($file_path);
        $new_path = $this->expectedMovePath($source, $destination, $file_path);
        $this->filesystem->shouldReceive('isWritable')->with($this->pathArgument($normalized_file_path))->andReturn(true)->once();

        $method = $copy ? 'copy' : 'rename';
        $this->filesystem->shouldReceive($method)
            ->with($this->pathArgument($normalized_file_path), $this->pathArgument($new_path))
            ->andReturn(true)
            ->once();
    }

    /**
     * Match paths that are equivalent after updater-style normalization.
     *
     * @param string $expected Expected path.
     * @return \Mockery\Matcher\Closure
     */
    protected function pathArgument($expected)
    {
        $expected = $this->canonicalPath($expected);

        return Mockery::on(function ($actual) use ($expected) {
            return $this->canonicalPath($actual) === $expected;
        });
    }

    /**
     * Normalize separators and duplicate slashes for cross-platform mocks.
     *
     * @param string $path Path to normalize.
     * @return string Comparable path.
     */
    protected function canonicalPath($path)
    {
        return preg_replace('#/+#', '/', $this->normalizeMovePath($path));
    }

    /**
     * Normalize a path the same way the updater move routine does.
     *
     * @param string $path Path before move-time normalization.
     * @return string Path with Windows separators converted.
     */
    protected function normalizeMovePath($path)
    {
        return str_replace("\\", "/", $path);
    }

    /**
     * Build the destination path expected from the updater move routine.
     *
     * @param string $source Source directory before normalization.
     * @param string $destination Destination directory before normalization.
     * @param string $path Source file path before normalization.
     * @return string Destination file path after move-time normalization.
     */
    protected function expectedMovePath($source, $destination, $path)
    {
        $source = $this->normalizeMovePath($source);
        $destination = $this->normalizeMovePath($destination);
        $path = $this->normalizeMovePath($path);

        return str_replace("//", "/", str_replace($source, $destination, $path));
    }

    /**
     * Build the theme install path the same way the updater does.
     *
     * @param string $themePath Base theme path from updater config.
     * @return string Theme install path passed into move/copy/delete.
     */
    protected function expectedThemeInstallPath($themePath)
    {
        return rtrim($themePath, DIRECTORY_SEPARATOR) . '/ee/';
    }

    protected function shouldCallDelete($directory, array $exclusions = [])
    {
        $this->filesystem->shouldReceive('getDirectoryContents')->with($directory)->andReturn([$directory . 'index.html'])->once();

        $this->filesystem->shouldReceive('isWritable')->with($directory . 'index.html')->andReturn(true)->once();
        $this->filesystem->shouldReceive('delete')->with($directory . 'index.html')->andReturn(true)->once();
    }
}
