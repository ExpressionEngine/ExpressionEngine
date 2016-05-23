<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Updater\FileUpdater;
use Mockery;

class FileUpdaterTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->filesystem = Mockery::mock('EllisLab\ExpressionEngine\Library\Filesystem\Filesystem');
		$this->config = Mockery::mock('EllisLab\ExpressionEngine\Service\Config\File');
		$this->verifier = Mockery::mock('EllisLab\ExpressionEngine\Service\Updater\Verifier');
		$this->logger = Mockery::mock('EllisLab\ExpressionEngine\Service\Updater\Logger');

		$this->logger->shouldReceive('log');

		$this->fileupdater = new Downloader($this->license_number, $this->payload_url, $this->curl, $this->filesystem, $this->zip_archive, $this->config, $this->verifier, $this->logger, $this->requirements, $this->sites);
	}

	public function testBackup()
	{

	}

	protected function move($source, $destination, Array $exclusions = [], $copy = FALSE)
	{
		$this->filesystem->shouldReceive('exists')->with($destination)->andReturn(TRUE);
		$this->filesystem->shouldReceive('isDir')->with($destination)->andReturn(TRUE);

		$this->filesystem->shouldReceive('getDirectoryContents')->with($source)->andReturn([]);
	}
}
