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

		$this->license_number = '1234-1234-1234-1234';
		$this->payload_url = 'http://0.0.0.0/ee.zip';

		$this->updater = new Updater($this->license_number, $this->payload_url, $this->curl, $this->filesystem, $this->zip_archive, $this->config);
	}

	private function getPartialMock($methods)
	{
		return Mockery::mock(
			'EllisLab\ExpressionEngine\Service\Updater\Updater['.$methods.']',
			array($this->license_number, $this->payload_url, $this->curl, $this->filesystem, $this->zip_archive, $this->config)
		);
	}

	public function tearDown()
	{
		$this->curl = NULL;
		$this->filesystem = NULL;
		$this->zip_archive = NULL;
		$this->config = NULL;
		$this->updater = NULL;
	}

	/**
	 * @expectedException EllisLab\ExpressionEngine\Service\Updater\UpdaterException
	 * @dataProvider badUpdaterConstructorProvider
	 */
	public function testBadConstructor($license_number, $payload_url)
	{
		$updater = new Updater($license_number, $payload_url, $this->curl, $this->filesystem, $this->zip_archive, $this->config);
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
		$updater = $this->getPartialMock('preflight,downloadPackage,unzipPackage,verifyZipContents,moveUpdater');

		$updater->shouldReceive('preflight')->once();
		$updater->shouldReceive('downloadPackage')->once();
		$updater->shouldReceive('unzipPackage')->once();
		$updater->shouldReceive('verifyZipContents')->once();
		$updater->shouldReceive('moveUpdater')->once();

		$updater->getUpdateFiles();
	}

	public function testGetSteps()
	{
		$steps = $this->updater->getSteps();

		$expected = array(
			'preflight',
			'downloadPackage',
			'unzipPackage',
			'verifyZipContents',
			'moveUpdater'
		);

		$this->assertEquals($expected, $steps);
	}

	public function testPreflight()
	{
		$this->config->shouldReceive('set')->with('is_site_on', 'n');

		$this->config->shouldReceive('get')
			->with('cache_path')
			->andReturn('cache/path/');

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
			// UpdaterException caught? Good!
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
			// UpdaterException caught? Good!
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
			// UpdaterException caught? Good!
		}
	}
}
