<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Updater\Downloader;
use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use Mockery;

class DownloaderTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->license = Mockery::mock('EllisLab\ExpressionEngine\Service\License\ExpressionEngineLicense');
		$this->curl = Mockery::mock('EllisLab\ExpressionEngine\Library\Curl\RequestFactory');
		$this->filesystem = Mockery::mock('EllisLab\ExpressionEngine\Library\Filesystem\Filesystem');
		$this->zip_archive = Mockery::mock('ZipArchive');
		$this->config = Mockery::mock('EllisLab\ExpressionEngine\Service\Config\File');
		$this->verifier = Mockery::mock('EllisLab\ExpressionEngine\Service\Updater\Verifier');
		$this->logger = Mockery::mock('EllisLab\ExpressionEngine\Service\Updater\Logger');
		$this->requirements = Mockery::mock('EllisLab\ExpressionEngine\Service\Updater\RequirementsCheckerLoader');
		$this->sites = Mockery::mock('EllisLab\ExpressionEngine\Library\Data\Collection');

		$this->logger->shouldReceive('log');

		$this->payload_url = 'http://0.0.0.0/ee.zip';

		$this->downloader = new Downloader($this->license, $this->payload_url, $this->curl, $this->filesystem, $this->zip_archive, $this->config, $this->verifier, $this->logger, $this->requirements, $this->sites);
	}

	private function getPartialMock($methods)
	{
		return Mockery::mock(
			'EllisLab\ExpressionEngine\Service\Updater\Downloader['.$methods.']',
			[$this->license, $this->payload_url, $this->curl, $this->filesystem, $this->zip_archive, $this->config, $this->verifier, $this->logger, $this->requirements, $this->sites]
		);
	}

	public function tearDown()
	{
		$this->license = NULL;
		$this->curl = NULL;
		$this->filesystem = NULL;
		$this->zip_archive = NULL;
		$this->config = NULL;
		$this->downloader = NULL;
		$this->verifier = NULL;
		$this->logger = NULL;
		$this->requirements = NULL;
		$this->sites = NULL;
	}

	public function testPreflight()
	{
		return;

		$this->config->shouldReceive('get')
			->with('theme_folder_path')
			->andReturn(NULL);

		$this->filesystem->shouldReceive('getFreeDiskSpace')
			->with(PATH_CACHE.'ee_update/')
			->andReturn(1048576000)
			->once();

		$this->filesystem->shouldReceive('mkDir');
		$this->filesystem->shouldReceive('isFile')->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('isDir')->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('delete')->twice();

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
			->andReturn(TRUE)
			->once();

		$this->filesystem->shouldReceive('isWritable')
			->with(PATH_CACHE.'ee_update/')
			->andReturn(TRUE)
			->once();

		$this->filesystem->shouldReceive('isWritable')
			->with(SYSPATH.'ee')
			->andReturn(TRUE)
			->once();

		$this->filesystem->shouldReceive('isWritable')
			->with(SYSPATH.'user/config/config.php')
			->andReturn(TRUE)
			->once();

		$this->downloader->preflight();

		$this->filesystem->shouldReceive('getFreeDiskSpace')
			->with(PATH_CACHE.'ee_update/')
			->andReturn(1234)
			->once();

		$this->filesystem->shouldReceive('mkDir');
		$this->filesystem->shouldReceive('isFile')->andReturn(TRUE);
		$this->filesystem->shouldReceive('isDir')->andReturn(TRUE)->twice();
		$this->filesystem->shouldReceive('delete')->times(4);

		try
		{
			$this->downloader->preflight();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(11, $e->getCode());
			$this->assertContains('1234', $e->getMessage());
		}

		$this->filesystem->shouldReceive('getFreeDiskSpace')
			->with(PATH_CACHE.'ee_update/')
			->andReturn(1048576000);

		$this->filesystem->shouldReceive('mkDir');
		$this->filesystem->shouldReceive('isFile')->andReturn(TRUE);
		$this->filesystem->shouldReceive('isDir')->andReturn(TRUE);
		$this->filesystem->shouldReceive('delete');

		$this->filesystem->shouldReceive('isWritable')
			->with(PATH_CACHE.'ee_update/')
			->andReturn(TRUE)
			->once();

		$this->filesystem->shouldReceive('isWritable')
			->with(SYSPATH.'ee/')
			->andReturn(TRUE);

		$this->downloader->preflight();

		$this->filesystem->shouldReceive('isFile')->andReturn(TRUE);
		$this->filesystem->shouldReceive('isDir')->andReturn(TRUE);
		$this->filesystem->shouldReceive('delete');

		$this->filesystem->shouldReceive('isWritable')
			->with(PATH_CACHE.'ee_update/')
			->andReturn(FALSE)
			->once();

		// I ran into the need to test for exceptions in various cases and
		// just setting a blanket exception expectation for the test wasn't
		// working because the only criteria for passing is that the exception
		// is thrown at least once, but I needed to specifically make sure it
		// was thrown x times
		try
		{
			$this->downloader->preflight();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(1, $e->getCode());
			$this->assertContains(PATH_CACHE.'ee_update/', $e->getMessage());
		}
	}

	public function testDownloadPackage()
	{
		$request = Mockery::mock('EllisLab\ExpressionEngine\Library\Curl\PostRequest');

		$this->license->shouldReceive('getRawLicense')->andReturn('1234');

		$this->curl->shouldReceive('post')->with(
				$this->payload_url,
				[
					'action' => 'download_update',
					'license' => '1234',
					'version' => APP_VER
				]
			)
			->once()
			->andReturn($request);

		$request->shouldReceive('exec')
			->once()
			->andReturn('some data');

		$request->shouldReceive('getHeader')
			->with('http_code')
			->once()
			->andReturn('200');

		$request->shouldReceive('getHeader')
			->with('Content-Type')
			->once()
			->andReturn('application/zip');

		$request->shouldReceive('getHeader')
			->with('Package-Signature')
			->andReturn('APMXXgjhZBuapY4NOdWxe7LDylqYueqm9ZPyjlqDTa6mCzwrL1DVSRsAQiqHBndwfXjPrFvQu1IkkKOTQU1GGHEfVcAUQMPttt5UwZsDoyaw/8YP8Xm5bxyvv0WACYDSihKFHsp8ndsqhHp21W2K5dJQVgo1jif+CFObT2ja5c0IK6SjN/dhEXaHZ8m85jqNfePYgqTT+taNbU7IWuFzx49AAe8KI5hDGkKS0a5DhVFdru1duMyLuQAEthw5RHoznDS4u/X48ILqDtaaApXNonD26bzZhxLwNwI9WuUg/1aOHqoiYdPQgWH2GvyxOKy8MmxwDuoZD4XuwaqfTRoYfw==');

		$this->filesystem->shouldReceive('mkDir');

		$this->filesystem->shouldReceive('write')
			->with(PATH_CACHE.'ee_update/ExpressionEngine.zip', 'some data', TRUE)
			->once();

		$this->filesystem->shouldReceive('hashFile')
			->with('sha384', PATH_CACHE.'ee_update/ExpressionEngine.zip')
			->once()
			->andReturn('fb7cc0d8a9c41a8ddf74bf7bc6f4e487a79c15c07f15116ad1ce3b0da7159577fff365db8ae7fe8cc463f15da7430d02');

		$this->downloader->downloadPackage();
	}

	public function testDownloadPackageExceptions()
	{
		$request = Mockery::mock('EllisLab\ExpressionEngine\Library\Curl\PostRequest');

		$this->license->shouldReceive('getRawLicense')->andReturn('1234');

		$this->curl->shouldReceive('post')->with(
				$this->payload_url,
				[
					'action' => 'download_update',
					'license' => '1234',
					'version' => APP_VER
				]
			)
			->andReturn($request);

		$request->shouldReceive('exec')
			->andReturn('some data');

		$request->shouldReceive('getHeader')
			->with('http_code')
			->twice()
			->andReturn('403');

		try
		{
			$this->downloader->downloadPackage();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(4, $e->getCode());
		}

		$request->shouldReceive('getHeader')
			->with('http_code')
			->andReturn('200');

		$request->shouldReceive('getHeader')
			->with('Content-Type')
			->twice()
			->andReturn('application/pdf');

		try
		{
			$this->downloader->downloadPackage();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(5, $e->getCode());
		}

		$request->shouldReceive('getHeader')
			->with('Content-Type')
			->andReturn('"application/zip"');

		$request->shouldReceive('getHeader')
			->with('Package-Signature')
			->once()
			->andReturn(FALSE);

		try
		{
			$this->downloader->downloadPackage();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(6, $e->getCode());
		}

		$request->shouldReceive('getHeader')
			->with('Package-Signature')
			->andReturn('APMXXgjhZBuapY4NOdWxe7LDylqYueqm9ZPyjlqDTa6mCzwrL1DVSRsAQiqHBndwfXjPrFvQu1IkkKOTQU1GGHEfVcAUQMPttt5UwZsDoyaw/8YP8Xm5bxyvv0WACYDSihKFHsp8ndsqhHp21W2K5dJQVgo1jif+CFObT2ja5c0IK6SjN/dhEXaHZ8m85jqNfePYgqTT+taNbU7IWuFzx49AAe8KI5hDGkKS0a5DhVFdru1duMyLuQAEthw5RHoznDS4u/X48ILqDtaaApXNonD26bzZhxLwNwI9WuUg/1aOHqoiYdPQgWH2GvyxOKy8MmxwDuoZD4XuwaqfTRoYfw==');

		$this->config->shouldReceive('set')->with('is_site_on', 'n');

		$this->filesystem->shouldReceive('mkDir');

		$this->filesystem->shouldReceive('write')
			->with(PATH_CACHE.'ee_update/ExpressionEngine.zip', 'some data', TRUE);

		$this->filesystem->shouldReceive('hashFile')
			->with('sha384', PATH_CACHE.'ee_update/ExpressionEngine.zip')
			->once()
			->andReturn('bad hash');

		try
		{
			$this->downloader->downloadPackage();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(7, $e->getCode());
		}
	}

	public function testUnzipPackage()
	{
		$this->filesystem->shouldReceive('mkDir')->with(PATH_CACHE.'ee_update/');
		$this->filesystem->shouldReceive('mkDir')->with(PATH_CACHE.'ee_update/ExpressionEngine');

		$this->zip_archive->shouldReceive('open')
			->with(PATH_CACHE.'ee_update/ExpressionEngine.zip')
			->once()
			->andReturn(TRUE);

		$this->zip_archive->shouldReceive('extractTo')
			->with(PATH_CACHE.'ee_update/ExpressionEngine')
			->once();

		$this->zip_archive->shouldReceive('close')->once();

		$this->downloader->unzipPackage();

		$this->zip_archive->shouldReceive('open')
			->with(PATH_CACHE.'ee_update/ExpressionEngine.zip')
			->once()
			->andReturn(2);

		try
		{
			$this->downloader->unzipPackage();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(8, $e->getCode());
		}
	}

	public function testVerifyExtractedPackage()
	{
		$this->filesystem->shouldReceive('mkDir')->with(PATH_CACHE.'ee_update/');
		$this->filesystem->shouldReceive('mkDir')->with(PATH_CACHE.'ee_update/ExpressionEngine');

		$this->verifier->shouldReceive('verifyPath')->with(PATH_CACHE.'ee_update/ExpressionEngine', PATH_CACHE.'ee_update/ExpressionEngine/system/ee/installer/updater/hash-manifest');

		$this->downloader->verifyExtractedPackage();
	}

	public function testCheckRequirements()
	{
		$this->filesystem->shouldReceive('mkDir')->with(PATH_CACHE.'ee_update/');
		$this->filesystem->shouldReceive('mkDir')->with(PATH_CACHE.'ee_update/ExpressionEngine');

		$this->requirements->shouldReceive('setClassPath')->with(PATH_CACHE.'ee_update/ExpressionEngine/system/ee/installer/updater/EllisLab/ExpressionEngine/Updater/Service/Updater/RequirementsChecker.php');
		$this->requirements->shouldReceive('check')->andReturn(TRUE)->once();

		$this->downloader->checkRequirements();

		$failures = [
			new MockRequirement('This thing is required.'),
			new MockRequirement('So is this.')
		];
		$this->requirements->shouldReceive('check')->andReturn($failures)->once();

		try
		{
			$this->downloader->checkRequirements();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(14, $e->getCode());
		}
	}

	public function testMoveUpdater()
	{
		$this->filesystem->shouldReceive('mkDir')->with(PATH_CACHE.'ee_update/');
		$this->filesystem->shouldReceive('rename')->with(
			PATH_CACHE.'ee_update/ExpressionEngine/system/ee/installer/updater',
			SYSPATH.'ee/updater'
		);

		// Protected method stashConfigs() called inside moveUpdater()
		$this->config->shouldReceive('get')
			->with('theme_folder_path')
			->andReturn(NULL);
		$this->sites->shouldReceive('getIterator')->andReturn($this->getSitesIterator());
		$this->filesystem->shouldReceive('write')->with(
			PATH_CACHE.'ee_update/configs.json',
			json_encode([
				'update_path' => PATH_CACHE.'ee_update/',
				'archive_path' => PATH_CACHE.'ee_update/ExpressionEngine',
				'theme_paths' => [
					1 => '/some/theme/path',
					2 => '/some/theme/path2'
				]
			]),
			TRUE
		)->twice();

		// Now moveUpdater()
		$this->verifier->shouldReceive('verifyPath')->with(
			SYSPATH . '/ee/updater',
			SYSPATH . '/ee/updater/hash-manifest',
			'system/ee/installer/updater'
		)->once();

		$this->config->shouldReceive('set')->with('is_system_on', 'n', true)->once();

		$this->downloader->moveUpdater();

		$exception = new UpdaterException('Something bad happened.', 23);
		$this->verifier->shouldReceive('verifyPath')->with(
			SYSPATH . '/ee/updater',
			SYSPATH . '/ee/updater/hash-manifest',
			'system/ee/installer/updater'
		)->andThrow($exception)->once();

		$this->filesystem->shouldReceive('deleteDir')->once();

		try
		{
			$this->downloader->moveUpdater();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(23, $e->getCode());
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

class MockRequirement
{
	private $message = '';

	public function __construct($message)
	{
		$this->message = $message;
	}

	public function getMessage()
	{
		return $this->message;
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
