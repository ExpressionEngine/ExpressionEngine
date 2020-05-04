<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Updater\Downloader;

use EllisLab\ExpressionEngine\Service\Updater\Downloader\Unpacker;
use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use Mockery;
use PHPUnit\Framework\TestCase;

class UnpackerTest extends TestCase {

	public function setUp()
	{
		$this->filesystem = Mockery::mock('EllisLab\ExpressionEngine\Library\Filesystem\Filesystem');
		$this->zip_archive = Mockery::mock('ZipArchive');
		$this->verifier = Mockery::mock('EllisLab\ExpressionEngine\Service\Updater\Verifier');
		$this->logger = Mockery::mock('EllisLab\ExpressionEngine\Service\Updater\Logger');
		$this->requirements = Mockery::mock('EllisLab\ExpressionEngine\Service\Updater\RequirementsCheckerLoader');

		$this->logger->shouldReceive('log'); // Logger's gonna log
		$this->filesystem->shouldReceive('mkDir'); // For update path creation

		$this->unpacker = new Unpacker($this->filesystem, $this->zip_archive, $this->verifier, $this->logger, $this->requirements);
	}

	public function tearDown()
	{
		$this->filesystem = NULL;
		$this->zip_archive = NULL;
		$this->verifier = NULL;
		$this->logger = NULL;
		$this->requirements = NULL;
		$this->unpacker = NULL;
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

		$this->unpacker->unzipPackage();

		$this->zip_archive->shouldReceive('open')
			->with(PATH_CACHE.'ee_update/ExpressionEngine.zip')
			->once()
			->andReturn(2);

		try
		{
			$this->unpacker->unzipPackage();
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

		$this->unpacker->verifyExtractedPackage();
	}

	public function testCheckRequirements()
	{
		$this->filesystem->shouldReceive('mkDir')->with(PATH_CACHE.'ee_update/');
		$this->filesystem->shouldReceive('mkDir')->with(PATH_CACHE.'ee_update/ExpressionEngine');

		$this->requirements->shouldReceive('setClassPath')->with(PATH_CACHE.'ee_update/ExpressionEngine/system/ee/installer/updater/EllisLab/ExpressionEngine/Updater/Service/Updater/RequirementsChecker.php');
		$this->requirements->shouldReceive('check')->andReturn(TRUE)->once();

		$this->unpacker->checkRequirements();

		$failures = [
			new MockRequirement('This thing is required.'),
			new MockRequirement('So is this.')
		];
		$this->requirements->shouldReceive('check')->andReturn($failures)->once();

		try
		{
			$this->unpacker->checkRequirements();
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

		// Now moveUpdater()
		$this->verifier->shouldReceive('verifyPath')->with(
			SYSPATH . '/ee/updater',
			SYSPATH . '/ee/updater/hash-manifest',
			'system/ee/installer/updater'
		)->once();

		$this->unpacker->moveUpdater();

		$exception = new UpdaterException('Something bad happened.', 23);
		$this->verifier->shouldReceive('verifyPath')->with(
			SYSPATH . '/ee/updater',
			SYSPATH . '/ee/updater/hash-manifest',
			'system/ee/installer/updater'
		)->andThrow($exception)->once();

		$this->filesystem->shouldReceive('deleteDir')->once();

		try
		{
			$this->unpacker->moveUpdater();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(23, $e->getCode());
		}
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
