<?php

namespace ExpressionEngine\Tests\Service\Updater\Downloader;

use ExpressionEngine\Service\Updater\Downloader\Preflight;
use ExpressionEngine\Service\Updater\UpdaterException;
use Mockery;
use PHPUnit\Framework\TestCase;

class PreflightTest extends TestCase
{
    public function setUp() : void
    {
        $this->filesystem = Mockery::mock('ExpressionEngine\Library\Filesystem\Filesystem');
        $this->config = Mockery::mock('ExpressionEngine\Service\Config\File');
        $this->logger = Mockery::mock('ExpressionEngine\Service\Updater\Logger');
        $this->theme_paths = ['/some/theme/path', '/some/theme/path2'];

        $this->logger->shouldReceive('log'); // Logger's gonna log
        $this->filesystem->shouldReceive('mkDir'); // For update path creation

        $this->preflight = new Preflight($this->filesystem, $this->logger, $this->config, $this->theme_paths);
    }

    public function tearDown() : void
    {
        $this->filesystem = null;
        $this->config = null;
        $this->logger = null;
        $this->preflight = null;


        Mockery::close();
    }

    public function testCheckDiskSpace()
    {
        $this->filesystem->shouldReceive('mkDir');

        $this->filesystem->shouldReceive('getFreeDiskSpace')
            ->with(PATH_CACHE.'ee_update/')
            ->andReturn(1048576000)
            ->once();

        $this->preflight->checkDiskSpace();

        $this->filesystem->shouldReceive('getFreeDiskSpace')
            ->with(PATH_CACHE.'ee_update/')
            ->andReturn(1234)
            ->once();

        try {
            $this->preflight->checkDiskSpace();
            $this->fail();
        } catch (UpdaterException $e) {
            $this->assertEquals(11, $e->getCode());
            $this->assertStringContainsString('1234', $e->getMessage());
        }
    }

    public function testCheckPermissions()
    {
        $this->config->shouldReceive('get')
            ->with('theme_folder_path')
            ->andReturn(null);

        $this->filesystem->shouldReceive('getDirectoryContents')
            ->with(SYSPATH.'ee/')
            ->andReturn([
                SYSPATH.'ee/ExpressionEngine/',
                SYSPATH.'ee/legacy/'
            ]);

        $theme_paths = [];
        foreach ($this->theme_paths as $theme_path) {
            $theme_paths[] = $theme_path;
            $theme_path .= '/ee/';

            $this->filesystem->shouldReceive('isWritable')
                ->with($theme_path)
                ->andReturn(true);

            $this->filesystem->shouldReceive('getDirectoryContents')
                ->with($theme_path)
                ->andReturn([
                    $theme_path.'/asset/',
                    $theme_path.'/cp/'
                ]);

            $this->filesystem->shouldReceive('isWritable')
                ->with($theme_path.'/asset/')
                ->andReturn(true);

            $this->filesystem->shouldReceive('isWritable')
                ->with($theme_path.'/cp/')
                ->andReturn(true);
        }

        $this->filesystem->shouldReceive('getDirectoryContents')
            ->with(SYSPATH.'ee/')
            ->andReturn($theme_paths);

        $this->filesystem->shouldReceive('isWritable')
            ->with(SYSPATH.'ee/ExpressionEngine/')
            ->andReturn(true);

        $this->filesystem->shouldReceive('isWritable')
            ->with(SYSPATH.'ee/legacy/')
            ->andReturn(true);

        $this->filesystem->shouldReceive('isWritable')
            ->with(PATH_CACHE.'')
            ->andReturn(true);

        $this->filesystem->shouldReceive('isWritable')
            ->with(PATH_CACHE.'ee_update/')
            ->andReturn(true);

        $this->filesystem->shouldReceive('isWritable')
            ->with(SYSPATH.'ee')
            ->andReturn(true);

        $this->filesystem->shouldReceive('isWritable')
            ->with(SYSPATH.'user/config/config.php')
            ->andReturn(true)
            ->once();

        $this->preflight->checkPermissions();

        $this->filesystem->shouldReceive('isWritable')
            ->with(SYSPATH.'user/config/config.php')
            ->andReturn(false)
            ->once();

        try {
            $this->preflight->checkPermissions();
            $this->fail();
        } catch (UpdaterException $e) {
            $this->assertEquals(1, $e->getCode());
        }
    }
}

class MockSite
{
    public $site_system_preferences;
}

class MockSystemPrefs
{
    public $theme_folder_path;
}
