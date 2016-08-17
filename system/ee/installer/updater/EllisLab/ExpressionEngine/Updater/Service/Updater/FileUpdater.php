<?php

namespace EllisLab\ExpressionEngine\Updater\Service\Updater;

use EllisLab\ExpressionEngine\Updater\Service\Updater\Logger;
use EllisLab\ExpressionEngine\Updater\Service\Updater\UpdaterException;
use EllisLab\ExpressionEngine\Updater\Library\Filesystem\Filesystem;
use EllisLab\ExpressionEngine\Updater\Service\Config\File;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Updater class
 *
 * @package		ExpressionEngine
 * @subpackage	Updater
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class FileUpdater {

	protected $filesystem = NULL;
	protected $config = NULL;
	protected $verifier = NULL;
	protected $logger = NULL;

	// Public for unit testing :/
	public $configs = [];

	public function __construct(Filesystem $filesystem, File $config, Verifier $verifier, Logger $logger)
	{
		$this->filesystem = $filesystem;
		$this->config = $config;
		$this->verifier = $verifier;
		$this->logger = $logger;
		$this->configs = $this->parseConfigs();
	}

	public function updateFiles()
	{
		$this->backupExistingInstallFiles();
		$this->moveNewInstallFiles();
		$this->verifyNewFiles();
	}

	/**
	 * Backs up the existing install files
	 */
	public function backupExistingInstallFiles()
	{
		$this->logger->log('Starting backup of existing installation');

		// First backup the contents of system/ee, excluding ourselves
		$this->move(
			SYSPATH.'ee/',
			$this->getBackupsPath() . 'system_ee/',
			[SYSPATH.'ee/updater']
		);

		// We'll only backup one theme folder, they _should_ all be the same
		// across sites
		$theme_path = array_values($this->configs['theme_paths'])[0];
		$theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

		$this->move($theme_path, $this->getBackupsPath() . 'themes_ee/');
	}

	/**
	 * Backs up the existing install files
	 */
	public function moveNewInstallFiles()
	{
		$this->logger->log('Moving new ExpressionEngine installation into place');

		// Move new system/ee folder contents into place
		$new_system_dir = $this->configs['archive_path'] . '/system/ee/';

		$this->move($new_system_dir, SYSPATH.'ee/');

		// Now move new themes into place
		$new_themes_dir = $this->configs['archive_path'] . '/themes/ee/';

		// If multiple theme paths exist, _copy_ the themes to each folder
		if (count(array_unique(array_values($this->configs['theme_paths']))) > 1)
		{
			$this->logger->log('Multiple theme paths detected, copying new themes folders into place');

			foreach ($this->configs['theme_paths'] as $theme_path)
			{
				$theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

				$this->move($new_themes_dir, $theme_path, [], TRUE);
			}
		}
		// Otherwise, just move the themes to the one themes folder
		else
		{
			$theme_path = array_values($this->configs['theme_paths'])[0];
			$theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

			$this->move($new_themes_dir, $theme_path);
		}
	}

	/**
	 * Verifies the newly-moved files made it over intact
	 */
	public function verifyNewFiles()
	{
		$this->logger->log('Verifying the integrity of the new ExpressionEngine files');

		$hash_manifiest = SYSPATH . 'ee/updater/hash-manifest';
		$exclusions = ['system/ee/installer/updater'];

		try {
			$this->verifier->verifyPath(
				SYSPATH . 'ee/',
				$hash_manifiest,
				'system/ee',
				$exclusions
			);

			if (count(array_unique(array_values($this->configs['theme_paths']))) > 1)
			{
				foreach ($this->configs['theme_paths'] as $theme_path)
				{
					$theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

					$this->verifier->verifyPath(
						$theme_path,
						$hash_manifiest,
						'themes/ee',
						$exclusions
					);
				}
			}
			// Otherwise, just move the themes to the one themes folder
			else
			{
				$theme_path = array_values($this->configs['theme_paths'])[0];
				$theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

				$this->verifier->verifyPath(
					$theme_path,
					$hash_manifiest,
					'themes/ee',
					$exclusions
				);
			}
		}
		catch (UpdaterException $e)
		{
			$this->logger->log('There was an error verifying the new installation\'s files: '.$e->getMessage());
			$this->rollbackFiles();
			throw new UpdaterException($e->getMessage(), $e->getCode());
		}

		$this->logger->log('New ExpressionEngine files successfully verified');
	}

	/**
	 * Rolls back to the previous installation's files and puts the new
	 * installation's files back in the extracted archive path in case we need
	 * to inspect them
	 */
	public function rollbackFiles()
	{
		$this->logger->log('Rolling back to previous installation\'s files');

		// Move back the new installation
		$this->move(
			SYSPATH.'ee/',
			$this->configs['archive_path'] . '/system/ee/',
			[SYSPATH.'ee/updater']
		);

		// Now move new themes into place
		$new_themes_dir = $this->configs['archive_path'] . '/themes/ee/';

		// If multiple theme paths exist, delete the contents of them since we
		// copied to them before
		if (count(array_unique(array_values($this->configs['theme_paths']))) > 1)
		{
			foreach ($this->configs['theme_paths'] as $theme_path)
			{
				$theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

				$this->delete($theme_path);
			}
		}
		// Otherwise, move the themes folder back to the archive folder
		else
		{
			$theme_path = array_values($this->configs['theme_paths'])[0];
			$theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

			$this->move($theme_path, $new_themes_dir);
		}

		// Now, restore backups
		$this->move(
			$this->getBackupsPath() . 'system_ee/',
			SYSPATH.'ee/'
		);

		// Copy themes backup to each theme folder
		if (count(array_unique(array_values($this->configs['theme_paths']))) > 1)
		{
			foreach ($this->configs['theme_paths'] as $theme_path)
			{
				$theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

				$this->move($this->getBackupsPath() . 'themes_ee/', $theme_path, [], TRUE);
			}
		}
		// Or move back if there is only one theme path
		else
		{
			$theme_path = array_values($this->configs['theme_paths'])[0];
			$theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

			$this->move($this->getBackupsPath() . 'themes_ee/', $theme_path);
		}
	}

	/**
	 * Moves contents of directories to another directory
	 *
	 * @param	string	$source			Source directory
	 * @param	string	$destination	Destination directory
	 * @param	array	$exclusions		Array of any paths to exlude when moving
	 * @param	boolean	$copy			When TRUE, copies instead of moves
	 */
	protected function move($source, $destination, Array $exclusions = [], $copy = FALSE)
	{
		if ( ! $this->filesystem->exists($destination))
		{
			$this->filesystem->mkDir($destination, FALSE);
		}
		elseif ( ! $this->filesystem->isDir($destination))
		{
			throw new UpdaterException('Destination path not a directory: '.$destination, 18);
		}
		elseif ( ! $this->filesystem->isWritable($destination))
		{
			throw new UpdaterException('Destination path not writable: '.$destination, 21);
		}

		$contents = $this->filesystem->getDirectoryContents($source);

		foreach ($contents as $path)
		{
			// Skip exclusions and .DS_Store
			if (in_array($path, $exclusions) OR strpos($path, '.DS_Store') !== FALSE)
			{
				continue;
			}

			$new_path = str_replace($source, $destination, $path);

			// Try to catch permissions errors before PHP's file I/O functions do
			if ( ! $this->filesystem->isWritable($path))
			{
				throw new UpdaterException("Cannot move ${path} to ${new_path}, path is not writable: ${path}", 19);
			}

			$this->logger->log('Moving '.$path.' to '.$new_path);

			$method = $copy ? 'copy' : 'rename';
			$this->filesystem->$method($path, $new_path);
		}
	}

	/**
	 * Deletes contents of a directory
	 *
	 * @param	string	$directory	Direcotry to delete the contents from
	 * @param	array	$exclusions	Array of any paths to exlude when deleting
	 */
	protected function delete($directory, Array $exclusions = [])
	{
		$contents = $this->filesystem->getDirectoryContents($directory);

		foreach ($contents as $path)
		{
			// Skip exclusions
			if (in_array($path, $exclusions))
			{
				continue;
			}

			if ( ! $this->filesystem->isWritable($path))
			{
				throw new UpdaterException("Cannot delete path ${path}, it is not writable", 20);
			}

			$this->logger->log('Deleting ' . $path);
			$this->filesystem->delete($path);
		}
	}

	/**
	 * Constructs and returns the backup path
	 *
	 * @return	string	Path to backups folder
	 */
	protected function getBackupsPath()
	{
		return $this->path() . 'backups/';
	}

	/**
	 * Optionally creates and returns the path in which we will be working with
	 * our files
	 *
	 * @return	string	Path to folder in the cache folder for working with updates
	 */
	protected function path()
	{
		$cache_path = $this->config->get('cache_path');

		if (empty($cache_path))
		{
			$cache_path = SYSPATH.'user'.DIRECTORY_SEPARATOR.'cache/';
		}
		else
		{
			$cache_path = rtrim($cache_path, DIRECTORY_SEPARATOR).'/';
		}

		return $cache_path . 'ee_update/';
	}

	/**
	 * Opens the configs.json file and parses the JSON as an associative array
	 *
	 * @return	array	Associative array of configs
	 */
	protected function parseConfigs()
	{
		$configs_path = $this->path() . 'configs.json';

		if ( ! $this->filesystem->exists($configs_path))
		{
			throw new UpdaterException('Cannot find '. $configs_path, 17);
		}

		return json_decode($this->filesystem->read($configs_path), TRUE);
	}
}

// EOF
