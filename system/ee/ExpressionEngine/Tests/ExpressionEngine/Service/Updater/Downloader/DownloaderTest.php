<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Updater\Downloader;

use EllisLab\ExpressionEngine\Service\Updater\Downloader\Downloader;
use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use Mockery;
use PHPUnit\Framework\TestCase;

class DownloaderTest extends TestCase {

	public function setUp()
	{
		$this->license = Mockery::mock('EllisLab\ExpressionEngine\Service\License\ExpressionEngineLicense');
		$this->curl = Mockery::mock('EllisLab\ExpressionEngine\Library\Curl\RequestFactory');
		$this->filesystem = Mockery::mock('EllisLab\ExpressionEngine\Library\Filesystem\Filesystem');
		$this->logger = Mockery::mock('EllisLab\ExpressionEngine\Service\Updater\Logger');
		$this->config = Mockery::mock('EllisLab\ExpressionEngine\Service\Config\File');

		$this->logger->shouldReceive('log'); // Logger's gonna log
		$this->filesystem->shouldReceive('mkDir'); // For update path creation

		$this->downloader = new Downloader($this->license, $this->curl, $this->filesystem, $this->logger, $this->config);
	}

	public function tearDown()
	{
		$this->license = NULL;
		$this->curl = NULL;
		$this->filesystem = NULL;
		$this->logger = NULL;
		$this->config = NULL;
		$this->downloader = NULL;
	}

	public function testDownloadPackage()
	{
		$request = Mockery::mock('EllisLab\ExpressionEngine\Library\Curl\PostRequest');

		$this->license->shouldReceive('getRawLicense')->andReturn('1234');
		$this->config->shouldReceive('get')->with('app_version')->andReturn('4.0.0');
		$this->config->shouldReceive('get')->with('site_url')->andReturn('my_site_url');

		$this->curl->shouldReceive('post')->with(
				'ee_package_url',
				[
					'action' => 'download_update',
					'license' => '1234',
					'version' => '4.0.0',
					'domain' => 'my_site_url'
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

		$this->downloader->downloadPackage('ee_package_url');
	}

	public function testDownloadPackageExceptions()
	{
		$request = Mockery::mock('EllisLab\ExpressionEngine\Library\Curl\PostRequest');

		$this->license->shouldReceive('getRawLicense')->andReturn('1234');
		$this->config->shouldReceive('get')->with('app_version')->andReturn('4.0.0');
		$this->config->shouldReceive('get')->with('site_url')->andReturn('my_site_url');

		$this->curl->shouldReceive('post')->with(
				'ee_package_url',
				[
					'action' => 'download_update',
					'license' => '1234',
					'version' => '4.0.0',
					'domain' => 'my_site_url'
				]
			)
			->andReturn($request);

		$request->shouldReceive('exec')
			->times(4)
			->andReturn('some data');

		$request->shouldReceive('getHeader')
			->with('http_code')
			->twice()
			->andReturn('403');

		try
		{
			$this->downloader->downloadPackage('ee_package_url');
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(4, $e->getCode());
		}

		$request->shouldReceive('getHeader')
			->with('http_code')
			->times(3)
			->andReturn('200');

		$request->shouldReceive('getHeader')
			->with('Content-Type')
			->twice()
			->andReturn('application/pdf');

		try
		{
			$this->downloader->downloadPackage('ee_package_url');
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
			$this->downloader->downloadPackage('ee_package_url');
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
			$this->downloader->downloadPackage('ee_package_url');
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(7, $e->getCode());
		}

		$request->shouldReceive('exec')
			->andReturn('{"error": "Cannot upgrade"}');

		$request->shouldReceive('getHeader')
			->with('http_code')
			->andReturn('500');

		try
		{
			$this->downloader->downloadPackage('ee_package_url');
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(20, $e->getCode());
			$this->assertEquals('Cannot upgrade', $e->getMessage());
		}
	}
}
