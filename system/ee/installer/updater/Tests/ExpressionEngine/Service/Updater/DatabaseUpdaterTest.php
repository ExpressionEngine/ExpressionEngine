<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Updater\Service\Updater\DatabaseUpdater;
use EllisLab\ExpressionEngine\Updater\Service\Updater\UpdaterException;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseUpdaterTest extends TestCase {

	public function setUp()
	{
		$this->filesystem = Mockery::mock('EllisLab\ExpressionEngine\Updater\Library\Filesystem\Filesystem');

		$this->filesystem->shouldReceive('getDirectoryContents')->with(
			SYSPATH . 'ee/installer/updates/'
		)->andReturn([
			'/some/path/to/updates/ud_3_00_00.php',
			'/some/path/to/updates/ud_3_00_01.php',
			'/some/path/to/updates/ud_3_01_00.php',
			'/some/path/to/updates/ud_3_04_00.php',
			'/some/path/to/updates/ud_3_02_00.php',
			'/some/path/to/updates/ud_3_05_00_dp_01.php',
			'/some/path/to/updates/ud_3_05_00_beta_01.php',
			'/some/path/to/updates/ud_3_05_00_beta_02.php',
			'/some/path/to/updates/ud_3_05_00_alpha_01.php',
			'/some/path/to/updates/ud_3_05_00_rc_01.php',
			'/some/path/to/updates/ud_3_05_00.php',
			'/some/path/to/updates/ud_4_00_00.php'
		]);

		$this->dbupdater = new DatabaseUpdater('3.1.0', $this->filesystem);
	}

	public function tearDown()
	{
		$this->filesystem = NULL;
		$this->dbupdater = NULL;
	}

	public function testGetUpdateFiles()
	{
		$this->assertEquals([
			'runUpdateFile[ud_3_02_00.php]',
			'runUpdateFile[ud_3_04_00.php]',
			'runUpdateFile[ud_3_05_00_dp_01.php]',
			'runUpdateFile[ud_3_05_00_alpha_01.php]',
			'runUpdateFile[ud_3_05_00_beta_01.php]',
			'runUpdateFile[ud_3_05_00_beta_02.php]',
			'runUpdateFile[ud_3_05_00_rc_01.php]',
			'runUpdateFile[ud_3_05_00.php]',
			'runUpdateFile[ud_4_00_00.php]'
		], $this->dbupdater->steps);
	}
}
