<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Updater\Downloader;

use EllisLab\ExpressionEngine\Service\Updater\Downloader\Preflight;
use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use Mockery;
use PHPUnit\Framework\TestCase;

class PreflightTest extends TestCase {

	public function setUp()
	{
		$this->filesystem = Mockery::mock('EllisLab\ExpressionEngine\Library\Filesystem\Filesystem');
		$this->config = Mockery::mock('EllisLab\ExpressionEngine\Service\Config\File');
		$this->logger = Mockery::mock('EllisLab\ExpressionEngine\Service\Updater\Logger');
		$this->sites = Mockery::mock('EllisLab\ExpressionEngine\Library\Data\Collection');

		$this->logger->shouldReceive('log'); // Logger's gonna log
		$this->filesystem->shouldReceive('mkDir'); // For update path creation

		$this->preflight = new Preflight($this->filesystem, $this->logger, $this->config, $this->sites);
	}

	public function tearDown()
	{
		$this->filesystem = NULL;
		$this->config = NULL;
		$this->logger = NULL;
		$this->sites = NULL;
		$this->preflight = NULL;
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

		try
		{
			$this->preflight->checkDiskSpace();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(11, $e->getCode());
			$this->assertContains('1234', $e->getMessage());
		}
	}

	public function testCheckPermissions()
	{
		$this->config->shouldReceive('get')
			->with('theme_folder_path')
			->andReturn(NULL);

		$this->filesystem->shouldReceive('getDirectoryContents')
			->with(SYSPATH.'ee/')
			->andReturn([
				SYSPATH.'ee/EllisLab/',
				SYSPATH.'ee/legacy/'
			]);

		$iterator = $this->getSitesIterator();
		$this->sites->shouldReceive('getIterator')->andReturn($iterator);
		$theme_paths = [];
		foreach ($iterator as $site)
		{
			$theme_path = $site->site_system_preferences->theme_folder_path . '/ee/';
			$this->filesystem->shouldReceive('isWritable')
				->with($theme_path)
				->andReturn(TRUE);

			$theme_paths[] = $site->site_system_preferences->theme_folder_path;
			$this->filesystem->shouldReceive('getDirectoryContents')
				->with($theme_path)
				->andReturn([
					$theme_path.'/asset/',
					$theme_path.'/cp/'
				]);

			$this->filesystem->shouldReceive('isWritable')
				->with($theme_path.'/asset/')
				->andReturn(TRUE);

			$this->filesystem->shouldReceive('isWritable')
				->with($theme_path.'/cp/')
				->andReturn(TRUE);
		}

		$this->filesystem->shouldReceive('getDirectoryContents')
			->with(SYSPATH.'ee/')
			->andReturn($theme_paths);

		$this->filesystem->shouldReceive('isWritable')
			->with(SYSPATH.'ee/EllisLab/')
			->andReturn(TRUE);

		$this->filesystem->shouldReceive('isWritable')
			->with(SYSPATH.'ee/legacy/')
			->andReturn(TRUE);

		$this->filesystem->shouldReceive('isWritable')
			->with(PATH_CACHE.'')
			->andReturn(TRUE);

		$this->filesystem->shouldReceive('isWritable')
			->with(PATH_CACHE.'ee_update/')
			->andReturn(TRUE);

		$this->filesystem->shouldReceive('isWritable')
			->with(SYSPATH.'ee')
			->andReturn(TRUE);

		$this->filesystem->shouldReceive('isWritable')
			->with(SYSPATH.'user/config/config.php')
			->andReturn(TRUE)
			->once();

		$this->preflight->checkPermissions();

		$this->filesystem->shouldReceive('isWritable')
			->with(SYSPATH.'user/config/config.php')
			->andReturn(FALSE)
			->once();

		try
		{
			$this->preflight->checkPermissions();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(1, $e->getCode());
		}
	}

	private function getSitesIterator()
	{
		// Protected method stashConfigs() called inside moveUpdater()
		$site1 = new MockSite();
		$site1->site_id = 1;
		$site1->site_system_preferences = new MockSystemPrefs();
		$site1->site_system_preferences->theme_folder_path = '/some/theme/path';

		$site2 = new MockSite();
		$site2->site_id = 2;
		$site2->site_system_preferences = new MockSystemPrefs();
		$site2->site_system_preferences->theme_folder_path = '/some/theme/path2';

		return new \ArrayIterator([$site1, $site2]);
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
