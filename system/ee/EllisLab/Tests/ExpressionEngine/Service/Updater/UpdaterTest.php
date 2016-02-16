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
		$this->filesystem->shouldReceive('isFile')->andReturn(TRUE);
		$this->filesystem->shouldReceive('isDir')->andReturn(TRUE);
		$this->filesystem->shouldReceive('delete')->twice();

		$this->filesystem->shouldReceive('isWritable')
			->with('cache/path/ee_update/')
			->andReturn(TRUE);

		$this->filesystem->shouldReceive('isWritable')
			->with(SYSPATH.'ee/')
			->andReturn(TRUE);

		define('PATH_THEMES', 'themes/path');
		$this->filesystem->shouldReceive('isWritable')
			->with(PATH_THEMES)
			->andReturn(TRUE);

		$this->updater->preflight();

		// TODO: test possible Preflight exceptions
	}
}
