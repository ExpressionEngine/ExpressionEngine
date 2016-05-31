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

		$this->config->shouldReceive('get')
			->with('cache_path')
			->andReturn('cache/path/');

		$configs_path = 'cache/path/ee_update/configs.json';
		$this->filesystem->shouldReceive('exists')->with($configs_path)->andReturn(TRUE);
		$this->filesystem->shouldReceive('read')->with($configs_path)->andReturn('{"update_path":"\/cache\/path\/ee_update\/","archive_path":"\/cache\/path\/ee_update\/ExpressionEngine","theme_paths":{"1":"\/themes\/"}}');

		$this->fileupdater = new FileUpdater($this->filesystem, $this->config, $this->verifier, $this->logger);
	}

	public function testBackupExistingInstallFiles()
	{
		$this->shouldCallMove(
			SYSPATH.'ee/',
			'cache/path/ee_update/backups/system_ee/',
			[SYSPATH.'ee/updater']
		);
		$this->shouldCallMove(
			'/themes/',
			'cache/path/ee_update/backups/themes_ee/'
		);

		$this->fileupdater->backupExistingInstallFiles();
	}

	public function testMoveNewInstallFiles()
	{
		$this->shouldCallMove(
			'/cache/path/ee_update/ExpressionEngine/system/ee/',
			SYSPATH.'ee/'
		);
		$this->shouldCallMove(
			'/cache/path/ee_update/ExpressionEngine/themes/ee/',
			'/themes/'
		);

		$this->fileupdater->moveNewInstallFiles();
	}

	public function testVerifyNewFiles()
	{
		$hash_manifiest = SYSPATH . '/ee/updater/hash-manifest';
		$exclusions = ['system/ee/installer/updater'];

		$this->verifier->shouldReceive('verifyPath')->with(
			SYSPATH . '/ee',
			$hash_manifiest,
			'system/ee',
			$exclusions
		)->andReturn(TRUE);

		$this->verifier->shouldReceive('verifyPath')->with(
			'/themes/ee',
			$hash_manifiest,
			'themes/ee',
			$exclusions
		)->andReturn(TRUE);

		$this->fileupdater->verifyNewFiles();
	}

	public function testRollbackFiles()
	{
		$this->shouldCallRollbackFiles();
		$this->fileupdater->rollbackFiles();
	}

	protected function shouldCallRollbackFiles()
	{
		$this->shouldCallMove(
			SYSPATH.'ee/',
			'/cache/path/ee_update/ExpressionEngine/system/ee/',
			[SYSPATH.'ee/updater']
		);

		$this->shouldCallMove(
			'/themes/',
			'/cache/path/ee_update/ExpressionEngine/themes/ee/'
		);

		$this->shouldCallMove(
			'cache/path/ee_update/backups/system_ee/',
			SYSPATH.'ee/'
		);

		$this->shouldCallMove(
			'cache/path/ee_update/backups/themes_ee/',
			'/themes/'
		);
	}

	protected function shouldCallMove($source, $destination, Array $exclusions = [], $copy = FALSE)
	{
		$this->filesystem->shouldReceive('exists')->with($destination)->andReturn(TRUE);
		$this->filesystem->shouldReceive('isDir')->with($destination)->andReturn(TRUE);

		$this->filesystem->shouldReceive('getDirectoryContents')->with($source)->andReturn([]);
	}
}
