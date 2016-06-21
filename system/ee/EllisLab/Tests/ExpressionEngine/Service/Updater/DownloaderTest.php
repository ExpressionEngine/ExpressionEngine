<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Updater\Downloader;
use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use Mockery;

class DownloaderTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->curl = Mockery::mock('EllisLab\ExpressionEngine\Library\Curl\RequestFactory');
		$this->filesystem = Mockery::mock('EllisLab\ExpressionEngine\Library\Filesystem\Filesystem');
		$this->zip_archive = Mockery::mock('ZipArchive');
		$this->config = Mockery::mock('EllisLab\ExpressionEngine\Service\Config\File');
		$this->verifier = Mockery::mock('EllisLab\ExpressionEngine\Service\Updater\Verifier');
		$this->logger = Mockery::mock('EllisLab\ExpressionEngine\Service\Updater\Logger');
		$this->requirements = Mockery::mock('EllisLab\ExpressionEngine\Service\Updater\RequirementsCheckerLoader');
		$this->sites = Mockery::mock('EllisLab\ExpressionEngine\Library\Data\Collection');

		$this->logger->shouldReceive('log');

		$this->license_number = '1234-1234-1234-1234';
		$this->payload_url = 'http://0.0.0.0/ee.zip';

		$this->downloader = new Downloader($this->license_number, $this->payload_url, $this->curl, $this->filesystem, $this->zip_archive, $this->config, $this->verifier, $this->logger, $this->requirements, $this->sites);
	}

	private function getPartialMock($methods)
	{
		return Mockery::mock(
			'EllisLab\ExpressionEngine\Service\Updater\Downloader['.$methods.']',
			[$this->license_number, $this->payload_url, $this->curl, $this->filesystem, $this->zip_archive, $this->config, $this->verifier, $this->logger, $this->requirements, $this->sites]
		);
	}

	public function tearDown()
	{
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

	/**
	 * @expectedException EllisLab\ExpressionEngine\Service\Updater\UpdaterException
	 * @dataProvider badUpdaterConstructorProvider
	 */
	public function testBadConstructor($license_number, $payload_url)
	{
		$updater = new Downloader($license_number, $payload_url, $this->curl, $this->filesystem, $this->zip_archive, $this->config, $this->verifier, $this->logger, $this->requirements, $this->sites);
	}

	public function badUpdaterConstructorProvider()
	{
		// No license
		$license_number = '';
		$payload_url = 'http://0.0.0.0/ee.zip';

		$return = [[$license_number, $payload_url]];

		// No payload
		$license_number = '1234-1234-1234-1234';
		$payload_url = '';

		// Nothing
		$return[] = [$license_number, $payload_url];

		$return[] = ['', ''];

		return $return;
	}

	public function testGetUpdate()
	{
		$downloader = $this->getPartialMock('preflight,downloadPackage,unzipPackage,verifyExtractedPackage,checkRequirements,moveUpdater');

		$downloader->shouldReceive('preflight')->once()->andReturn('downloadPackage');
		$downloader->shouldReceive('downloadPackage')->once()->andReturn('unzipPackage');
		$downloader->shouldReceive('unzipPackage')->once()->andReturn('verifyExtractedPackage');
		$downloader->shouldReceive('verifyExtractedPackage')->once()->andReturn('checkRequirements');
		$downloader->shouldReceive('checkRequirements')->once()->andReturn('moveUpdater');
		$downloader->shouldReceive('moveUpdater')->once()->andReturn(FALSE);

		$downloader->getUpdate();
	}

	public function testPreflight()
	{
		$this->config->shouldReceive('get')
			->with('cache_path')
			->andReturn('cache/path/');

		$this->config->shouldReceive('get')
			->with('theme_folder_path')
			->andReturn(NULL);

		$this->filesystem->shouldReceive('getFreeDiskSpace')
			->with('cache/path/ee_update/')
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
			->with('cache/path/ee_update/')
			->andReturn(TRUE)
			->once();

		$this->filesystem->shouldReceive('isWritable')
			->with(SYSPATH.'ee/')
			->andReturn(TRUE)
			->once();

		$next_step = $this->downloader->preflight();
		$this->assertEquals('downloadPackage', $next_step);return;

		$this->filesystem->shouldReceive('getFreeDiskSpace')
			->with('cache/path/ee_update/')
			->andReturn(1234)
			->once();

		$this->filesystem->shouldReceive('mkDir');
		$this->filesystem->shouldReceive('isFile')->andReturn(TRUE)->twice();
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
			->with('cache/path/ee_update/')
			->andReturn(1048576000);

		$this->filesystem->shouldReceive('mkDir');
		$this->filesystem->shouldReceive('isFile')->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('isDir')->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('delete')->twice();

		$this->filesystem->shouldReceive('isWritable')
			->with('cache/path/ee_update/')
			->andReturn(TRUE)
			->once();

		$this->filesystem->shouldReceive('isWritable')
			->with(SYSPATH.'ee/')
			->andReturn(TRUE)
			->once();

		$this->filesystem->shouldReceive('isWritable')
			->with(PATH_THEMES)
			->andReturn(TRUE)
			->once();

		$this->downloader->preflight();

		$this->filesystem->shouldReceive('isWritable')
			->with('cache/path/ee_update/')
			->andReturn(TRUE)
			->times(3);

		$this->filesystem->shouldReceive('isWritable')
			->with(SYSPATH.'ee/')
			->andReturn(TRUE)
			->times(3);

		$this->filesystem->shouldReceive('isWritable')
			->with(PATH_THEMES)
			->andReturn(TRUE)
			->times(3);

		$this->filesystem->shouldReceive('isFile')->andReturn(FALSE)->once();
		$this->filesystem->shouldReceive('isDir')->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('delete')->once();

		$this->downloader->preflight();

		$this->filesystem->shouldReceive('isFile')->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('isDir')->andReturn(FALSE)->once();
		$this->filesystem->shouldReceive('delete')->once();

		$this->downloader->preflight();

		$this->filesystem->shouldReceive('isFile')->andReturn(FALSE)->once();
		$this->filesystem->shouldReceive('isDir')->andReturn(FALSE)->once();
		$this->filesystem->shouldReceive('delete')->never();

		$this->downloader->preflight();

		$this->filesystem->shouldReceive('isFile')->andReturn(TRUE);
		$this->filesystem->shouldReceive('isDir')->andReturn(TRUE);
		$this->filesystem->shouldReceive('delete');

		$this->filesystem->shouldReceive('isWritable')
			->with('cache/path/ee_update/')
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
		}

		$this->filesystem->shouldReceive('isWritable')
			->with('cache/path/ee_update/')
			->andReturn(TRUE)
			->once();

		$this->filesystem->shouldReceive('isWritable')
			->with(SYSPATH.'ee/')
			->andReturn(FALSE)
			->once();

		try
		{
			$this->downloader->preflight();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(2, $e->getCode());
		}

		$this->filesystem->shouldReceive('isWritable')
			->with('cache/path/ee_update/')
			->andReturn(TRUE)
			->once();

		$this->filesystem->shouldReceive('isWritable')
			->with(SYSPATH.'ee/')
			->andReturn(TRUE)
			->once();

		$this->filesystem->shouldReceive('isWritable')
			->with(PATH_THEMES)
			->andReturn(FALSE)
			->once();

		try
		{
			$this->downloader->preflight();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(3, $e->getCode());
		}
	}

	public function testDownloadPackage()
	{
		$request = Mockery::mock('EllisLab\ExpressionEngine\Library\Curl\PostRequest');

		$this->curl->shouldReceive('post')->with(
				$this->payload_url,
				['license' => $this->license_number]
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
			->with('Package-Hash')
			->once()
			->andReturn('f893f7fddb3804258d26c4c3c107dc3ba6618046');

		$this->config->shouldReceive('get')
			->with('cache_path')
			->andReturn('cache/path/');
		$this->filesystem->shouldReceive('mkDir');

		$this->filesystem->shouldReceive('write')
			->with('cache/path/ee_update/ExpressionEngine.zip', 'some data', TRUE)
			->once();

		$this->filesystem->shouldReceive('sha1File')
			->with('cache/path/ee_update/ExpressionEngine.zip')
			->once()
			->andReturn('f893f7fddb3804258d26c4c3c107dc3ba6618046');

		$request->shouldReceive('getHeader')
			->with('Package-Hash')
			->once()
			->andReturn('f893f7fddb3804258d26c4c3c107dc3ba6618046');

		$next_step = $this->downloader->downloadPackage();
		$this->assertEquals('unzipPackage', $next_step);
	}

	public function testDownloadPackageExceptions()
	{
		$request = Mockery::mock('EllisLab\ExpressionEngine\Library\Curl\PostRequest');

		$this->curl->shouldReceive('post')->with(
				$this->payload_url,
				['license' => $this->license_number]
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
			->with('Package-Hash')
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
			->with('Package-Hash')
			->andReturn('f893f7fddb3804258d26c4c3c107dc3ba6618046');

		$this->config->shouldReceive('set')->with('is_site_on', 'n');
		$this->config->shouldReceive('get')
			->with('cache_path')
			->andReturn('cache/path/');
		$this->filesystem->shouldReceive('mkDir');

		$this->filesystem->shouldReceive('write')
			->with('cache/path/ee_update/ExpressionEngine.zip', 'some data', TRUE);

		$this->filesystem->shouldReceive('sha1File')
			->with('cache/path/ee_update/ExpressionEngine.zip')
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
		$this->config->shouldReceive('get')
			->with('cache_path')
			->andReturn('cache/path/');
		$this->filesystem->shouldReceive('mkDir')->with('cache/path/ee_update/');
		$this->filesystem->shouldReceive('mkDir')->with('cache/path/ee_update/ExpressionEngine');

		$this->zip_archive->shouldReceive('open')
			->with('cache/path/ee_update/ExpressionEngine.zip')
			->once()
			->andReturn(TRUE);

		$this->zip_archive->shouldReceive('extractTo')
			->with('cache/path/ee_update/ExpressionEngine')
			->once();

		$this->zip_archive->shouldReceive('close')->once();

		$next_step = $this->downloader->unzipPackage();
		$this->assertEquals('verifyExtractedPackage', $next_step);

		$this->zip_archive->shouldReceive('open')
			->with('cache/path/ee_update/ExpressionEngine.zip')
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
		$this->config->shouldReceive('get')
			->with('cache_path')
			->andReturn('cache/path/');
		$this->filesystem->shouldReceive('mkDir')->with('cache/path/ee_update/');
		$this->filesystem->shouldReceive('mkDir')->with('cache/path/ee_update/ExpressionEngine');

		$this->verifier->shouldReceive('verifyPath')->with('cache/path/ee_update/ExpressionEngine', 'cache/path/ee_update/ExpressionEngine/system/ee/installer/updater/hash-manifest');

		$next_step = $this->downloader->verifyExtractedPackage();
		$this->assertEquals('checkRequirements', $next_step);
	}

	public function testCheckRequirements()
	{
		$this->config->shouldReceive('get')
			->with('cache_path')
			->andReturn('cache/path/');
		$this->filesystem->shouldReceive('mkDir')->with('cache/path/ee_update/');
		$this->filesystem->shouldReceive('mkDir')->with('cache/path/ee_update/ExpressionEngine');

		$this->requirements->shouldReceive('setClassPath')->with('cache/path/ee_update/ExpressionEngine/system/ee/installer/updater/EllisLab/ExpressionEngine/Service/Updater/RequirementsChecker.php');
		$this->requirements->shouldReceive('check')->andReturn(TRUE)->once();

		$next_step = $this->downloader->checkRequirements();
		$this->assertEquals('moveUpdater', $next_step);

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
			$this->assertContains('This thing is required.', $e->getMessage());
			$this->assertContains('So is this.', $e->getMessage());
		}
	}

	public function testMoveUpdater()
	{
		$this->config->shouldReceive('get')
			->with('cache_path')
			->andReturn('cache/path/');
		$this->filesystem->shouldReceive('mkDir')->with('cache/path/ee_update/');
		$this->filesystem->shouldReceive('rename')->with(
			'cache/path/ee_update/ExpressionEngine/system/ee/installer/updater',
			SYSPATH.'ee/updater'
		);

		// Protected method stashConfigs() called inside moveUpdater()
		$this->config->shouldReceive('get')
			->with('theme_folder_path')
			->andReturn(NULL);
		$this->sites->shouldReceive('getIterator')->andReturn($this->getSitesIterator());
		$this->filesystem->shouldReceive('write')->with(
			'cache/path/ee_update/configs.json',
			json_encode([
				'update_path' => 'cache/path/ee_update/',
				'archive_path' => 'cache/path/ee_update/ExpressionEngine',
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

		$this->config->shouldReceive('set')->with('is_system_on', 'n')->once();

		$next_step = $this->downloader->moveUpdater();
		$this->assertEquals(FALSE, $next_step);

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
			$this->assertEquals('Something bad happened.', $e->getMessage());
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
