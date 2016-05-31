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
		// Single themes folder
		$this->shouldCallMove(
			SYSPATH.'ee/',
			'cache/path/ee_update/backups/system_ee/',
			[SYSPATH.'ee/updater']
		);
		$this->shouldCallMove(
			'/themes/ee/',
			'cache/path/ee_update/backups/themes_ee/'
		);

		$this->fileupdater->backupExistingInstallFiles();

		// Multiple themes folders, but are the same
		$this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/themes/'];
		$this->shouldCallMove(
			SYSPATH.'ee/',
			'cache/path/ee_update/backups/system_ee/',
			[SYSPATH.'ee/updater']
		);
		$this->shouldCallMove(
			'/themes/ee/',
			'cache/path/ee_update/backups/themes_ee/'
		);

		$this->fileupdater->backupExistingInstallFiles();

		// Multiple unique themes folders
		$this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/some/other/site/themes/'];
		$this->shouldCallMove(
			SYSPATH.'ee/',
			'cache/path/ee_update/backups/system_ee/',
			[SYSPATH.'ee/updater']
		);
		$this->shouldCallMove(
			'/themes/ee/',
			'cache/path/ee_update/backups/themes_ee/'
		);

		$this->fileupdater->backupExistingInstallFiles();
	}

	public function testMoveNewInstallFiles()
	{
		// Single themes folder
		$this->shouldCallMove(
			'/cache/path/ee_update/ExpressionEngine/system/ee/',
			SYSPATH.'ee/'
		);
		$this->shouldCallMove(
			'/cache/path/ee_update/ExpressionEngine/themes/ee/',
			'/themes/ee/'
		);

		$this->fileupdater->moveNewInstallFiles();

		// Multiple themes folders, but are the same
		$this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/themes/'];
		$this->shouldCallMove(
			'/cache/path/ee_update/ExpressionEngine/system/ee/',
			SYSPATH.'ee/'
		);
		$this->shouldCallMove(
			'/cache/path/ee_update/ExpressionEngine/themes/ee/',
			'/themes/ee/'
		);

		$this->fileupdater->moveNewInstallFiles();

		// Multiple unique themes folders
		$this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/some/other/site/themes/'];
		$this->shouldCallMove(
			'/cache/path/ee_update/ExpressionEngine/system/ee/',
			SYSPATH.'ee/'
		);

		foreach ($this->fileupdater->configs['theme_paths'] as $theme_path)
		{
			$this->shouldCallMove(
				'/cache/path/ee_update/ExpressionEngine/themes/ee/',
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

		// Multiple themes folders, but are the same
		$this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/themes/'];
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

		// Multiple themes folders, but are the same
		$this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/themes/'];
		$this->shouldCallRollbackFiles();
		$this->fileupdater->rollbackFiles();

		// Multiple unique themes folders
		$this->fileupdater->configs['theme_paths'] = [1 => '/themes/', 2 => '/some/other/site/themes/'];
		$this->shouldCallMove(
			SYSPATH.'ee/',
			'/cache/path/ee_update/ExpressionEngine/system/ee/',
			[SYSPATH.'ee/updater']
		);
		foreach ($this->fileupdater->configs['theme_paths'] as $theme_path)
		{
			$this->shouldCallDelete($theme_path.'ee/');
		}
		$this->shouldCallMove(
			'cache/path/ee_update/backups/system_ee/',
			SYSPATH.'ee/'
		);
		foreach ($this->fileupdater->configs['theme_paths'] as $theme_path)
		{
			$this->shouldCallMove(
				'cache/path/ee_update/backups/themes_ee/',
				$theme_path.'ee/',
				[],
				TRUE
			);
		}

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
			'/themes/ee/',
			'/cache/path/ee_update/ExpressionEngine/themes/ee/'
		);

		$this->shouldCallMove(
			'cache/path/ee_update/backups/system_ee/',
			SYSPATH.'ee/'
		);

		$this->shouldCallMove(
			'cache/path/ee_update/backups/themes_ee/',
			'/themes/ee/'
		);
	}

	protected function shouldCallMove($source, $destination, Array $exclusions = [], $copy = FALSE)
	{
		$this->filesystem->shouldReceive('exists')->with($destination)->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('isDir')->with($destination)->andReturn(TRUE)->once();

		$file_path = $source.'index.html';
		$this->filesystem->shouldReceive('getDirectoryContents')->with($source)->andReturn([$file_path])->once();

		$new_path = str_replace($source, $destination, $file_path);
		$this->filesystem->shouldReceive('isWritable')->with($file_path)->andReturn(TRUE)->once();
		$this->filesystem->shouldReceive('isWritable')->with($new_path)->andReturn(TRUE)->once();

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
