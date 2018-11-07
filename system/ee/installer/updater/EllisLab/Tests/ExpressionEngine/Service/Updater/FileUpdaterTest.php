<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Updater\Service\Updater\FileUpdater;
use EllisLab\ExpressionEngine\Updater\Service\Updater\UpdaterException;
use Mockery;
use PHPUnit\Framework\TestCase;

class FileUpdaterTest extends TestCase {

	public function setUp()
	{
		$this->filesystem = Mockery::mock('EllisLab\ExpressionEngine\Updater\Library\Filesystem\Filesystem');
		$this->verifier = Mockery::mock('EllisLab\ExpressionEngine\Updater\Service\Updater\Verifier');
		$this->logger = Mockery::mock('EllisLab\ExpressionEngine\Updater\Service\Updater\Logger');

		$this->logger->shouldReceive('log');

		$configs_path = PATH_CACHE.'ee_update/configs.json';
		$this->filesystem->shouldReceive('exists')->with($configs_path)->andReturn(TRUE);
		$this->filesystem->shouldReceive('read')->with($configs_path)->andReturn(
				json_encode([
				'update_path' => PATH_CACHE.'ee_update/',
				'archive_path' => PATH_CACHE.'ee_update/ExpressionEngine',
				'theme_paths' => ['/themes/']
			])
		);

		$this->archive_path = PATH_CACHE.'ee_update/ExpressionEngine/';
		$this->backups_path = PATH_CACHE.'ee_update/backups/';

		$this->fileupdater = new FileUpdater($this->filesystem, $this->verifier, $this->logger);
	}

	public function testBackupExistingInstallFiles()
	{
		// Single themes folder
		$this->shouldCallMove(
			SYSPATH.'ee/',
			$this->backups_path.'system_ee/',
			[SYSPATH.'ee/updater']
		);
		$this->shouldCallMove(
			'/themes/ee/',
			$this->backups_path.'themes_ee/'
		);

		$this->fileupdater->backupExistingInstallFiles();

		// Multiple themes folders, but are the same
		$this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/themes/'];
		$this->shouldCallMove(
			SYSPATH.'ee/',
			$this->backups_path.'system_ee/',
			[SYSPATH.'ee/updater']
		);
		$this->shouldCallMove(
			'/themes/ee/',
			$this->backups_path.'themes_ee/'
		);

		$this->fileupdater->backupExistingInstallFiles();

		// Multiple unique themes folders
		$this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/some/other/site/themes/'];
		$this->shouldCallMove(
			SYSPATH.'ee/',
			$this->backups_path.'system_ee/',
			[SYSPATH.'ee/updater']
		);
		$this->shouldCallMove(
			'/themes/ee/',
			$this->backups_path.'themes_ee/'
		);

		$this->fileupdater->backupExistingInstallFiles();
	}

	public function testMoveNewInstallFiles()
	{
		// Single themes folder
		$this->shouldCallMove(
			$this->archive_path.'system/ee/',
			SYSPATH.'ee/'
		);
		$this->shouldCallMove(
			$this->archive_path.'themes/ee/',
			'/themes/ee/'
		);

		$this->fileupdater->moveNewInstallFiles();

		// Multiple unique themes folders
		$this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/some/other/site/themes/'];
		$this->shouldCallMove(
			$this->archive_path.'system/ee/',
			SYSPATH.'ee/'
		);

		foreach ($this->fileupdater->configs['theme_paths'] as $theme_path)
		{
			$this->shouldCallMove(
				$this->archive_path.'themes/ee/',
				$theme_path.'ee/',
				[],
				TRUE
			);
		}

		$this->fileupdater->moveNewInstallFiles();
	}

	public function testVerifyNewFiles()
	{
		$hash_manifiest = SYSPATH . 'ee/updater/hash-manifest';
		$exclusions = ['system/ee/installer/updater'];

		$this->verifier->shouldReceive('verifyPath')->with(
			SYSPATH . 'ee/',
			$hash_manifiest,
			'system/ee',
			$exclusions
		)->andReturn(TRUE)->once();

		$this->verifier->shouldReceive('verifyPath')->with(
			'/themes/ee',
			$hash_manifiest,
			'themes/ee',
			$exclusions
		)->andReturn(TRUE)->once();

		$this->fileupdater->verifyNewFiles();

		// Multiple unique themes folders
		$this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/some/other/site/themes/'];
		$this->verifier->shouldReceive('verifyPath')->with(
			SYSPATH . 'ee/',
			$hash_manifiest,
			'system/ee',
			$exclusions
		)->andReturn(TRUE)->once();

		foreach ($this->fileupdater->configs['theme_paths'] as $theme_path)
		{
			$this->verifier->shouldReceive('verifyPath')->with(
				$theme_path.'ee/',
				$hash_manifiest,
				'themes/ee',
				$exclusions
			)->andReturn(TRUE)->once();
		}

		$this->fileupdater->verifyNewFiles();
	}

	public function testRollbackFiles()
	{
		$this->shouldCallRollbackFiles();
		$this->fileupdater->rollbackFiles();

		// Multiple unique themes folders
		$this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/some/other/site/themes/'];
		$this->shouldCallMove(
			SYSPATH.'ee/',
			$this->archive_path.'system/ee/',
			[SYSPATH.'ee/updater']
		);
		foreach ($this->fileupdater->configs['theme_paths'] as $theme_path)
		{
			$this->shouldCallDelete($theme_path.'ee/');
		}
		$this->shouldCallMove(
			$this->backups_path.'system_ee/',
			SYSPATH.'ee/'
		);
		foreach ($this->fileupdater->configs['theme_paths'] as $theme_path)
		{
			$this->shouldCallMove(
				$this->backups_path.'themes_ee/',
				$theme_path.'ee/',
				[],
				TRUE
			);
		}

		$this->fileupdater->rollbackFiles();
	}

	public function testMove()
	{
		// move() is protected, so we'll go through the backup method and test
		// via our mocks; we've also already pretty well tested what happens
		// under ideal circumstances, so we'll only manufacture failures here

		$source = SYSPATH.'ee/';
		$destination = $this->backups_path.'system_ee/';

		// Destination directory doesn't exist?
		$this->filesystem->shouldReceive('exists')->with($destination)->andReturn(FALSE)->once();
		$this->filesystem->shouldReceive('mkDir')->with($destination, FALSE)->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('getDirectoryContents')->with($source)->andReturn([])->once();

		$source = '/themes/ee/';
		$destination = $this->backups_path.'themes_ee/';

		$this->filesystem->shouldReceive('exists')->with($destination)->andReturn(FALSE)->once();
		$this->filesystem->shouldReceive('mkDir')->with($destination, FALSE)->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('getDirectoryContents')->with($source)->andReturn([])->once();

		$this->fileupdater->backupExistingInstallFiles();

		// Destination isn't a directory
		$source = SYSPATH.'ee/';
		$destination = $this->backups_path.'system_ee/';
		$this->filesystem->shouldReceive('exists')->with($destination)->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('isDir')->with($destination)->andReturn(FALSE)->once();

		try
		{
			$this->fileupdater->backupExistingInstallFiles();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(18, $e->getCode());
		}

		// Destination isn't writable
		$source = SYSPATH.'ee/';
		$destination = $this->backups_path.'system_ee/';
		$this->filesystem->shouldReceive('exists')->with($destination)->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('isDir')->with($destination)->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('isWritable')->with($destination)->andReturn(FALSE)->once();

		try
		{
			$this->fileupdater->backupExistingInstallFiles();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(21, $e->getCode());
		}

		// Should exclude files
		$this->filesystem->shouldReceive('exists')->with($destination)->andReturn(FALSE)->once();
		$this->filesystem->shouldReceive('mkDir')->with($destination, FALSE)->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('getDirectoryContents')->with($source)->andReturn([
			$source.'index.html',
			$source.'updater',
			$source.'.DS_Store',
		])->once();

		$new_path = str_replace($source, $destination, $source.'index.html');
		$this->filesystem->shouldReceive('isWritable')->with($source.'index.html')->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('rename')->with($source.'index.html', $new_path)->andReturn(TRUE)->once();

		$source = '/themes/ee/';
		$destination = $this->backups_path.'themes_ee/';

		$this->filesystem->shouldReceive('exists')->with($destination)->andReturn(FALSE)->once();
		$this->filesystem->shouldReceive('mkDir')->with($destination, FALSE)->andReturn(TRUE)->once();

		$file_path = $source.'index.html';
		$this->filesystem->shouldReceive('getDirectoryContents')->with($source)->andReturn([])->once();

		$this->fileupdater->backupExistingInstallFiles();

		// Should complain if an attempted move path isn't writable
		$source = SYSPATH.'ee/';
		$destination = $this->backups_path.'system_ee/';
		$this->filesystem->shouldReceive('exists')->with($destination)->andReturn(FALSE)->once();
		$this->filesystem->shouldReceive('mkDir')->with($destination, FALSE)->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('getDirectoryContents')->with($source)->andReturn([
			$source.'index.html',
		])->once();

		$new_path = str_replace($source, $destination, $source.'index.html');
		$this->filesystem->shouldReceive('isWritable')->with($source.'index.html')->andReturn(FALSE)->once();

		try
		{
			$this->fileupdater->backupExistingInstallFiles();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(19, $e->getCode());
		}
	}

	public function testDelete()
	{
		// We just want to test that it fails to delete if not writable, we've
		// tested the function works under correct circumstances elsewhere

		// Multiple unique themes folders
		$this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/some/other/site/themes/'];
		$this->shouldCallMove(
			SYSPATH.'ee/',
			$this->archive_path.'system/ee/',
			[SYSPATH.'ee/updater']
		);
		$directory = '/themes/ee/';
		$this->filesystem->shouldReceive('getDirectoryContents')->with($directory)->andReturn([$directory.'index.html'])->once();
		$this->filesystem->shouldReceive('isWritable')->with($directory.'index.html')->andReturn(FALSE)->once();

		try
		{
			$this->fileupdater->rollbackFiles();
			$this->fail();
		}
		catch (UpdaterException $e)
		{
			$this->assertEquals(20, $e->getCode());
		}
	}

	protected function shouldCallRollbackFiles()
	{
		$this->shouldCallMove(
			SYSPATH.'ee/',
			$this->archive_path.'system/ee/',
			[SYSPATH.'ee/updater']
		);

		$this->shouldCallMove(
			'/themes/ee/',
			$this->archive_path.'themes/ee/'
		);

		$this->shouldCallMove(
			PATH_CACHE.'ee_update/backups/system_ee/',
			SYSPATH.'ee/'
		);

		$this->shouldCallMove(
			PATH_CACHE.'ee_update/backups/themes_ee/',
			'/themes/ee/',
			[],
			TRUE
		);
	}

	protected function shouldCallMove($source, $destination, Array $exclusions = [], $copy = FALSE)
	{
		$this->filesystem->shouldReceive('exists')->with($destination)->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('isDir')->with($destination)->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('isWritable')->with($destination)->andReturn(TRUE)->once();

		$file_path = $source.'index.html';
		$this->filesystem->shouldReceive('getDirectoryContents')->with($source)->andReturn([$file_path])->once();

		$new_path = str_replace($source, $destination, $file_path);
		$this->filesystem->shouldReceive('isWritable')->with($file_path)->andReturn(TRUE)->once();

		$method = $copy ? 'copy' : 'rename';
		$this->filesystem->shouldReceive($method)->with($file_path, $new_path)->andReturn(TRUE)->once();
	}

	protected function shouldCallDelete($directory, Array $exclusions = [])
	{
		$this->filesystem->shouldReceive('getDirectoryContents')->with($directory)->andReturn([$directory.'index.html'])->once();

		$this->filesystem->shouldReceive('isWritable')->with($directory.'index.html')->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('delete')->with($directory.'index.html')->andReturn(TRUE)->once();
	}
}
