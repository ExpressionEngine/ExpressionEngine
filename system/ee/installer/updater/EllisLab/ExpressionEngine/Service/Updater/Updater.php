<?php

namespace EllisLab\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;
use EllisLab\ExpressionEngine\Service\Config\File;

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
class Updater {

	protected $filesystem = NULL;
	protected $config = NULL;
	protected $verifier = NULL;
	protected $configs = [];

	public function __construct(Filesystem $filesystem, File $config, Verifier $verifier)
	{
		$this->filesystem = $filesystem;
		$this->config = $config;
		$this->verifier = $verifier;
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
		// Move new system/ee folder contents into place
		$new_system_dir = $this->configs['archive_path'] . '/system/ee/';

		$this->move($new_system_dir, SYSPATH.'ee/');

		// Now move new themes into place
		$new_themes_dir = $this->configs['archive_path'] . '/themes/ee/';

		// If multiple theme paths exist, _copy_ the themes to each folder
		if (count(array_unique(array_values($this->configs['theme_paths']))) > 1)
		{
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
	 * Moves contents of directories to another directory
	 *
	 * @param	string	$source			Source directory
	 * @param	string	$destination	Destination directory
	 * @param	array	$excludions		Array of any paths to exlude when moving
	 * @param	boolean	$copy			Destination directory
	 */
	protected function move($source, $destination, $exclusions = [], $copy = FALSE)
	{
		if ( ! $this->filesystem->exists($destination))
		{
			$this->filesystem->mkDir($destination, FALSE);
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

			$method = $copy ? 'copy' : 'rename';
			$this->filesystem->$method($path, $new_path);
		}
	}

	/**
	 * Verifies the newly-moved files made it over intact
	 */
	public function verifyNewFiles()
	{
		try {
			$this->verifier->verifyPath(
				SYSPATH . '/ee',
				SYSPATH . '/ee/updater/hash-manifest',
				'system/ee'
			);

			if (count(array_unique(array_values($this->configs['theme_paths']))) > 1)
			{
				foreach ($this->configs['theme_paths'] as $theme_path)
				{
					$theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

					$this->verifier->verifyPath(
						$theme_path,
						SYSPATH . '/ee/updater/hash-manifest',
						'themes/ee'
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
					SYSPATH . '/ee/updater/hash-manifest',
					'themes/ee'
				);
			}
		}
		catch (UpdaterException $e)
		{
			// TODO: Start rollback process
			throw new UpdaterException($e->getMessage(), $e->getCode());
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
