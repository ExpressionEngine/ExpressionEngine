<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Updater\Updater;
use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use Mockery;

class UpdaterTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->curl = Mockery::mock('EllisLab\ExpressionEngine\Library\Curl\RequestFactory');
		$this->filesystem = Mockery::mock('EllisLab\ExpressionEngine\Library\Filesystem\Filesystem');
		$this->zip_archive = Mockery::mock('ZipArchive');
		$this->config = Mockery::mock('EllisLab\ExpressionEngine\Service\Config\File');
		$this->verifier = Mockery::mock('EllisLab\ExpressionEngine\Service\Updater\Verifier');
		$this->logger = Mockery::mock('EllisLab\ExpressionEngine\Service\Updater\Logger');

		$this->logger->shouldReceive('log');

		$this->license_number = '1234-1234-1234-1234';
		$this->payload_url = 'http://0.0.0.0/ee.zip';

		$this->updater = new Updater($this->license_number, $this->payload_url, $this->curl, $this->filesystem, $this->zip_archive, $this->config, $this->verifier, $this->logger);
	}

	private function getPartialMock($methods)
	{
		return Mockery::mock(
			'EllisLab\ExpressionEngine\Service\Updater\Updater['.$methods.']',
			array($this->license_number, $this->payload_url, $this->curl, $this->filesystem, $this->zip_archive, $this->config, $this->verifier, $this->logger)
		);
	}

	public function tearDown()
	{
		$this->curl = NULL;
		$this->filesystem = NULL;
		$this->zip_archive = NULL;
		$this->config = NULL;
		$this->updater = NULL;
		$this->verifier = NULL;
	}

	/**
	 * @expectedException EllisLab\ExpressionEngine\Service\Updater\UpdaterException
	 * @dataProvider badUpdaterConstructorProvider
	 */
	public function testBadConstructor($license_number, $payload_url)
	{
		$updater = new Updater($license_number, $payload_url, $this->curl, $this->filesystem, $this->zip_archive, $this->config, $this->verifier, $this->logger);
	}

	public function badUpdaterConstructorProvider()
	{
		// No license
		$license_number = '';
		$payload_url = 'http://0.0.0.0/ee.zip';

		$return = array(array($license_number, $payload_url));

		// No payload
		$license_number = '1234-1234-1234-1234';
		$payload_url = '';

		// Nothing
		$return[] = array($license_number, $payload_url);

		$return[] = array('', '');

		return $return;
	}

	public function testGetUpdateFiles()
	{
		$updater = $this->getPartialMock('preflight,downloadPackage,unzipPackage,verifyExtractedPackage,moveFiles');

		$updater->shouldReceive('preflight')->once();
		$updater->shouldReceive('downloadPackage')->once();
		$updater->shouldReceive('unzipPackage')->once();
		$updater->shouldReceive('verifyExtractedPackage')->once();
		$updater->shouldReceive('moveFiles')->once();

		$updater->getUpdateFiles();
	}

	public function testGetSteps()
	{
		$steps = $this->updater->getSteps();

		$expected = array(
			'preflight',
			'downloadPackage',
			'unzipPackage',
			'verifyExtractedPackage',
			'moveFiles'
		);

		$this->assertEquals($expected, $steps);
	}

	public function testPreflight()
	{
		$this->config->shouldReceive('set')->with('is_site_on', 'n');

		$this->config->shouldReceive('get')
			->with('cache_path')
			->andReturn('cache/path/');

		$this->filesystem->shouldReceive('getFreeDiskSpace')
			->with('cache/path/ee_update/')
			->andReturn(1048576000)
			->once();

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

		define('PATH_THEMES', 'themes/path');
		$this->filesystem->shouldReceive('isWritable')
			->with(PATH_THEMES)
			->andReturn(TRUE)
			->once();

		$this->updater->preflight();

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
			$this->updater->preflight();
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

		$this->updater->preflight();

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

		$this->updater->preflight();

		$this->filesystem->shouldReceive('isFile')->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('isDir')->andReturn(FALSE)->once();
		$this->filesystem->shouldReceive('delete')->once();

		$this->updater->preflight();

		$this->filesystem->shouldReceive('isFile')->andReturn(FALSE)->once();
		$this->filesystem->shouldReceive('isDir')->andReturn(FALSE)->once();
		$this->filesystem->shouldReceive('delete')->never();

		$this->updater->preflight();

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
			$this->updater->preflight();
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
			$this->updater->preflight();
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
			$this->updater->preflight();
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
				array('license' => $this->license_number)
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

		$this->updater->downloadPackage();
	}

	public function testDownloadPackageExceptions()
	{
		$request = Mockery::mock('EllisLab\ExpressionEngine\Library\Curl\PostRequest');

		$this->curl->shouldReceive('post')->with(
				$this->payload_url,
				array('license' => $this->license_number)
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
			$this->updater->downloadPackage();
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
			$this->updater->downloadPackage();
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
			$this->updater->downloadPackage();
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
			$this->updater->downloadPackage();
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

		$this->updater->unzipPackage();

		$this->zip_archive->shouldReceive('open')
			->with('cache/path/ee_update/ExpressionEngine.zip')
			->once()
			->andReturn(2);

		try
		{
			$this->updater->unzipPackage();
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

		$this->verifier->shouldReceive('verifyPath')->with('cache/path/ee_update/ExpressionEngine', 'cache/path/ee_update/ExpressionEngine/system/ee/updater/hash-manifest');

		$this->updater->verifyExtractedPackage();
	}
}
